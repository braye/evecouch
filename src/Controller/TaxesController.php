<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Seat\Eseye\Exceptions\RequestFailedException;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use App\Api\Esi;
use App\CouchDB\DocumentManager;
use App\Entity\CorpTaxableTransaction;
use App\Entity\CorpTaxConfig;
use App\Form\Taxes\TaxConfigForm;
use App\Repository\CorpTaxableTransactionRepository;
use App\Entity\User;

class TaxesController extends AbstractController
{
    // the ref_types the EVE api uses to categorize taxes, the stuff we're taxing on
    // TODO: consider making this configurable using the CorpTaxConfig?
    const BOUNTY_REF_TYPES = ['bounty_prize', 'bounty_prizes'];

    /**
     * @Route("/taxes", name="taxes")
     */
    public function index(Request $request)
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } catch(AccessDeniedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => 'Please log in to access this page.'
            ]);
        }

        $year = $request->query->get('year');
        $month = $request->query->get('month');

        if(empty($year))
            $year = date("Y");
        if(empty($month))
            $month = date("m");

        $user = $this->getUser();
        $esi = Esi::getApiHandleForUser($user);

        $roles = $user->getEveRoles();
        try{
            $corpInfo = $esi->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $user->getCorporationId()
            ]);

            if(empty($corpInfo->alliance_id))
                return $this->render('taxes/index.html.twig', [
                    'warning_message' => 'Your corporation is not in an alliance. This module won\'t be of much use to you.'
                ]);
            
            $allianceInfo = $esi->invoke('get', '/alliances/{alliance_id}/', [
                'alliance_id' => $corpInfo->alliance_id
            ]);

            $allianceCorps = $esi->invoke('get', '/alliances/{alliance_id}/corporations/', [
                'alliance_id' => $corpInfo->alliance_id
            ]);
        } catch (RequestFailedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $isDirector = in_array('Director', $roles);
        $taxConfig = $this->getDoctrine()->getRepository(CorpTaxConfig::class)->find($corpInfo->alliance_id);
        // directors in executor corps can view all corp payments and set up taxing for the alliance
        if(($user->getCorporationId() == $allianceInfo->executor_corporation_id) && $isDirector) {
            if(empty($taxConfig)){
                return $this->redirectToRoute('taxConfig');
            }

            $corps = [];
            $allianceCorpInfo = [];
            foreach($allianceCorps as $corp){
                $allianceCorpInfo[$corp] = $esi->invoke('get', '/corporations/{corporation_id}/', [
                    'corporation_id' => $corp
                ]);
                $corps[] = $corp;
            }
            $repo = $this->getDoctrine()->getRepository(CorpTaxableTransaction::class);
            $view = $repo->getMultiCorpTaxView($corps, $year, $month);
            
            $results = [];
            foreach($view as $result){
                $obj = new \StdClass();
                $obj->corpId = $result['key'][0];
                //this is so ugly omg
                $obj->corpName = $allianceCorpInfo[$obj->corpId]->name;
                unset($allianceCorpInfo[$obj->corpId]);
                $obj->amount = $result['value'] * $taxConfig->getTaxRate();
                $results[] = $obj;
            }

            return $this->render('taxes/alliance.html.twig', [
                'alliance_name' => $allianceInfo->name,
                'alliance_id' => $corpInfo->alliance_id,
                'results' => $results,
                'year' => $year,
                'month' => $month,
                'unavailable_corps' => $allianceCorpInfo,
                'is_executor' => true
            ]);
        } else if ($isDirector) {
            // show overview of your corp
            if(empty($taxConfig)){
                return $this->render('taxes/index.html.twig', [
                    'warning_message' => 'Your corporation\'s alliance has not been set up in the tax system. Contact a director of its executor corporation to log in and set it up.'
                ]);
            }

            $repo = $this->getDoctrine()->getRepository(CorpTaxableTransaction::class);
            $view = $repo->getCorpTaxView($user->getCorporationId(), $year, $month);

            return $this->render('taxes/alliance.html.twig', [
                'alliance_name' => $allianceInfo->name,
                'alliance_id' => $corpInfo->alliance_id,
                'results' => $view[0],
                'year' => $year,
                'month' => $month
            ]);
        } else {
            return $this->render('taxes/index.html.twig', [
                'warning_message' => 'This module is restricted to Corporation directors only.'
            ]);
        }
    }
    
    /**
     * @Route("/taxes/update", name="updateTaxes")
     */
    public function updateAllianceCorporations()
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } catch(AccessDeniedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => 'Please log in to access this page.'
            ]);
        }

        $user = $this->getUser();
        $esi = Esi::getApiHandleForUser($user);

        try{
            $corpInfo = $esi->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $user->getCorporationId()
            ]);

            if(empty($corpInfo->alliance_id))
                return $this->render('taxes/index.html.twig', [
                    'warning_message' => 'Your corporation is not in an alliance. This module won\'t be of much use to you.'
                ]);
            
            $allianceInfo = $esi->invoke('get', '/alliances/{alliance_id}/', [
                'alliance_id' => $corpInfo->alliance_id
            ]);

            $allianceCorps = $esi->invoke('get', '/alliances/{alliance_id}/corporations/', [
                'alliance_id' => $corpInfo->alliance_id
            ]);
        } catch (RequestFailedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $results = [];
        foreach($allianceCorps as $corp){
            $results[$corp]['success'] = $this->updateTransactionsForCorporation($corp, $corpInfo->alliance_id);
        }

        foreach($results as $corp => &$result){
            $result['info'] = $esi->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $corp
            ]);
        }

        return $this->render('taxes/update.html.twig', [
            'results' => $results
        ]);
    }

    /**
     * @Route("/taxes/config", name="taxConfig")
     */
    public function updateTaxConfig(Request $request)
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } catch(AccessDeniedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => 'Please log in to access this page.'
            ]);
        }

        $user = $this->getUser();
        $esi = Esi::getApiHandleForUser($user);

        try{
            $corpInfo = $esi->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $user->getCorporationId()
            ]);

            if(empty($corpInfo->alliance_id))
                return $this->render('taxes/index.html.twig', [
                    'warning_message' => 'Your corporation is not in an alliance. This module won\'t be of much use to you.'
                ]);
            
            $allianceInfo = $esi->invoke('get', '/alliances/{alliance_id}/', [
                'alliance_id' => $corpInfo->alliance_id
            ]);
        } catch (RequestFailedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $isDirector = in_array('Director', $user->getEveRoles());
        $taxConfig = $this->getDoctrine()->getRepository(CorpTaxConfig::class)->find($corpInfo->alliance_id);
        // only directors of alliance executor corps can configure tax rates
        if(($user->getCorporationId() == $allianceInfo->executor_corporation_id) && $isDirector) {
            if(empty($taxConfig)){
                $taxConfig = new CorpTaxConfig();
                $taxConfig->setAllianceId($corpInfo->alliance_id);
            }
            $form = $this->createForm(TaxConfigForm::class, $taxConfig);
            $form->handleRequest($request);
            
            if($form->isSubmitted() && $form->isValid()) {
                $taxConfig = $form->getData();
                $dm = new DocumentManager('corp_tax_config');
                $dm->save($taxConfig);
                return $this->redirectToRoute('taxes');
            }

            return $this->render('taxes/taxconfig.html.twig', [
                'alliance_name' => $allianceInfo->name,
                'alliance_id' => $corpInfo->alliance_id,
                'form' => $form->createView()
            ]);

        } else {
            $this->redirectToRoute('taxes');
        }

    }

    /**
     * Read the corporation's journal entries, looking for taxable income (bounties, mission reward taxes)
     *
     * @return bool
     */
    private function updateTransactionsForCorporation(int $corpId, int $allianceId): bool
    {
        $repo = $this->getDoctrine()->getRepository(User::class);
        $directors = $repo->getDirectorsByCorp($corpId);
        if($directors->count() == 0)
            return false;
        $user = $repo->find($directors[0]['id']);
        $esi = Esi::getApiHandleForUser($user);
        $dm = new DocumentManager('corp_taxes');
        $pageNum = 1;
        $emptyResult = false;
        $taxConfig = $this->getDoctrine()->getRepository(CorpTaxConfig::class)->find($allianceId);
        if(empty($taxConfig))
            return false;

        try{
            $wallets = $esi->invoke('get', '/corporations/{corporation_id}/wallets', [
                'corporation_id' => $corpId
            ]);
            
            foreach($wallets as $wallet){
                while(!$emptyResult){
                    $esi->page($pageNum);
                    $transactions = $esi->invoke('get', '/corporations/{corporation_id}/wallets/{division}/journal', [
                        'corporation_id' => $corpId,
                        'division' => $wallet->division
                    ]);
                    
                    if($transactions->count() == 0){
                        // we've reached the end of the journal
                        $emptyResult = true;
                        break;
                    }

                    foreach($transactions as $transaction){
                        if(in_array($transaction->ref_type, $this::BOUNTY_REF_TYPES)){
                            $existingTx = $this->getDoctrine()->getRepository(CorpTaxableTransaction::class)->find($transaction->id);
                            if(empty($existingTx)){
                                $date = new \DateTime($transaction->date);
                                $tx = new CorpTaxableTransaction();
                                $tx->setTransactionId($transaction->id);
                                $tx->setAmount($transaction->amount);
                                $tx->setMonth($date->format('m'));
                                $tx->setYear($date->format('Y'));
                                $tx->setRefType($transaction->ref_type);
                                $tx->setCorporationId($corpId);
                                $dm->save($tx);
                            }
                        }
                    }
                    $pageNum++;
                }
            }
        } catch (RequestFailedException $e){
            return false;
        }
        return true;
    }
}
