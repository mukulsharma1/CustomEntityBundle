<?php

namespace Pim\Bundle\CustomEntityBundle\MassEditConnector\Reader;

use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Item\ItemReaderInterface;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Pim\Bundle\CustomEntityBundle\Entity\Repository\CustomEntityRepository;
use Pim\Component\ReferenceData\ConfigurationRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Romain Monceau <romain@akeneo.com>
 */
class ReferenceDataReader implements ItemReaderInterface, StepExecutionAwareInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var EntityManager */
    protected $em;

    /** @var ArrayCollection */
    protected $referenceDatas;

    /** @var  ConfigurationRegistry */
    protected $registry;

    /**
     * @param EntityManager                       $em
     * @param ConfigurationRegistry               $registry
     */
    public function __construct(
        EntityManager $em,
        ConfigurationRegistry $registry
    ) {
        $this->em = $em;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (null === $this->referenceDatas) {

            $jobExecution = $this->stepExecution->getJobExecution();
            $jobParameters = $jobExecution->getJobParameters();
            $this->referenceDatas = $this->getReferenceDatas(
                $jobParameters->get('reference_data'),
                $jobParameters->get('ids')
            );
        }

        $result = $this->referenceDatas->current();

        if (!empty($result)) {
            $this->stepExecution->incrementSummaryInfo('read');
            $this->referenceDatas->next();
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Get reference data entities from ids
     *
     * @param string $referenceDataName
     * @param array  $ids
     *
     * @return ArrayCollection
     */
    protected function getReferenceDatas($referenceDataName, array $ids)
    {
        $repository = $this->getRepository($referenceDataName);

        return $repository->findByIds($ids);
    }

    /**
     * @param string $referenceDataName
     *
     * @return CustomEntityRepository
     */
    protected function getRepository($referenceDataName)
    {
        return $this->em->getRepository($referenceDataName);
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
