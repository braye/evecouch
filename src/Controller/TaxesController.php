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

class TaxesController extends AbstractController
{
    // the ref_types the EVE api uses to categorize taxes, the stuff we're taxing on
    const BOUNTY_REF_TYPES = ['bounty_prize', 'bounty_prizes', 'agent_mission_time_bonus_reward_corporation_tax'];

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
        } catch (RequestFailedException $e) {
            return $this->render('taxes/index.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $hasWalletAccess = !empty(array_intersect(['Accountant', 'Junior_Accountant'], $roles));
        $isDirector = in_array('Director', $roles);
        $taxConfig = $this->getDoctrine()->getRepository(CorpTaxConfig::class)->find($corpInfo->alliance_id);
        // director in executor corp first
        if(($user->getCorporationId() == $allianceInfo->executor_corporation_id) && ($hasWalletAccess && $isDirector)) {
            // this is a bit of an awful mess, but it's required to create tax configurations
            if(empty($taxConfig)){                
                $taxConfig = new CorpTaxConfig();
                $taxConfig->setAllianceId($corpInfo->alliance_id);
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
            }
            $this->updateTransactions($taxConfig);
            return $this->json(['op success']);
        } else if ($hasWalletAccess && $isDirector) {
            // show overview of your corp
            if(empty($taxConfig)){
                return $this->render('taxes/index.html.twig', [
                    'warning_message' => 'Your corporation\'s alliance has not been set up in the tax system. Contact a director of the executor corporation to log in and set it up.'
                ]);
            }
            $this->updateTransactions($taxConfig);
            return $this->json(['op success']);
        } else {
            return $this->render('taxes/index.html.twig', [
                'warning_message' => 'You are not a director or you do not have access to corp wallets.'
            ]);
        }
    }

    /**
     * Read the corporation's journal entries, looking for taxable income (bounties, mission reward taxes)
     *
     * @return void
     */
    private function updateTransactions(CorpTaxConfig $config)
    {
        $user = $this->getUser();
        $esi = Esi::getApiHandleForUser($user);
        $dm = new DocumentManager('corp_taxes');
        $pageNum = 1;
        $emptyResult = false;
        $taxRate = $config->getTaxRate();

        while(!$emptyResult){
            try{
                $esi->page($pageNum);
                $transactions = $esi->invoke('get', '/corporations/{corporation_id}/wallets/1/journal', [
                    'corporation_id' => $user->getCorporationId(),
                ]);
            } catch (RequestFailedException $e) {
                throw $e;
            }
            
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
                        $tx->setAmount($transaction->amount * $taxRate);
                        $tx->setMonth($date->format('m'));
                        $tx->setYear($date->format('Y'));
                        $tx->setRefType($transaction->ref_type);
                        $tx->setCorporationId($user->getCorporationId());
                        $dm->save($tx);
                    }
                }
            }
            $pageNum++;
        }
    }
}