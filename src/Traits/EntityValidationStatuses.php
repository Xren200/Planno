<?php

namespace App\Traits;

use App\PlanningBiblio\ValidationAwareEntity;
use App\Entity\Absence;
use App\Entity\Agent;

trait EntityValidationStatuses
{
    public function entity_state($valide_paire)
    {
        if (empty($valide_paire)) return 0;

        $valide_n2 = $valide_paire[0];
        $valide_n1 = $valide_paire[1];

        // Accepted level 2.
        if ($valide_n2 > 0) return 1;

        // Rejected level 2.
        if ($valide_n2 < 0) return -1;

        // Accepted level 1
        if ($valide_n1 > 0) return 2;

        // Rejected level 1
        if ($valide_n1 < 0) return -2;

        return 0;
    }

    public function setStatusesParams($agent_ids, $module, $entity_id = null, $needsValidationL1)
    {
        if (!$agent_ids) {
            throw new \Exception("EntityValidationStatuses::setStatusesParams: No agent");
        }

        $show_select = false;

        $entity = new ValidationAwareEntity($module, $entity_id);
        list($entity_state, $entity_state_desc) = $entity->status();

        // At this point, overtime entities
        // and holiday are treated the same.
        // This was not the cas in ValidationAwareEntity.
        $module = $module == 'overtime' ? 'holiday' : $module;

        if ($module == 'absence' && $entity_id) {
            $absence = $this->entityManager->getRepository(Absence::class)->find($entity_id);
            $statuses = [$absence->getValidLevel1(), $absence->getValidLevel2()];
            $entity_state = $this->entity_state($statuses);
        }

        $adminN1 = true;
        $adminN2 = true;
        foreach ($agent_ids as $id) {
            list($N1, $N2) = $this->entityManager
                ->getRepository(Agent::class)
                ->setModule($module)
                ->forAgent($id)
                ->getValidationLevelFor($_SESSION['login_id']);

            $adminN1 = $N1 ? $adminN1 : false;
            $adminN2 = $N2 ? $adminN2 : false;
        }

        $entity->setAdminFlags($adminN1, $adminN2);

        $show_select = $adminN1 || $adminN2;
        $show_n1 = $adminN1 || $adminN2;
        $show_n2 = $adminN2;

        // Only adminN2 can change statuses of
        // validated N2 entities.
        if (in_array($entity_state, [1, -1]) && !$adminN2) {
            $show_select = 0;
        }

        // Prevent user without right L1 to directly validate l2
        if (!$adminN1 && $entity_state == 0 && $entity->needsValidationL1()) {
            $show_select = 0;
        }

        // Accepted N2 hildays cannot be changed.
        if ($entity_state == 1 && $module == 'holiday') {
            $show_select = 0;
        }

        if($statuses){
            $this->templateParams([
                'entity_state_desc' => $entity_state_desc,
                'entity_state'      => $entity_state,
                'show_select'       => $show_select,
                'show_n1'           => $show_n1,
                'show_n2'           => $show_n2,

                'debug_evs'    => [
                    'module'       => $module,
                    'entity_state' => $entity_state,
                    'statuses' => $statuses,
                    'show_select'  => (bool)$show_select,
                    'N1'           => $N1,
                    'N2'           => $N2,
                    'adminN2'      => $adminN2
                ],
            ]);
        }
        else{
            $this->templateParams([
                'entity_state_desc' => $entity_state_desc,
                'entity_state'      => $entity_state,
                'show_select'       => $show_select,
                'show_n1'           => $show_n1,
                'show_n2'           => $show_n2,

                'debug_evs'    => [
                'module'       => $module,
                'show_select'  => (bool)$show_select,
                'N1'           => $N1,
                'N2'           => $N2,
                'adminN2'      => $adminN2
                ],
            ]);
        }
    }
}
