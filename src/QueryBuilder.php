<?php

namespace T4webInfrastructure;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Predicate;
use T4webDomainInterface\Infrastructure\CriteriaInterface;

class QueryBuilder
{

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param CriteriaInterface $criteria
     * @return Select
     */
    public function getSelect(CriteriaInterface $criteria)
    {
        $select = new Select();
        $select->from($this->config->getTable($criteria->getEntityName()));
        $this->buildPredicate($select, $criteria);
        $this->buildOrPredicate($select, $criteria);
        $this->buildOrder($select, $criteria);

        if (!empty($criteria->getLimit())) {
            $select->limit($criteria->getLimit());
        }

        if (!empty($criteria->getOffset())) {
            $select->offset($criteria->getOffset());
        }

        $this->buildRelations($select, $criteria);

        return $select;
    }

    private function buildPredicate(Select $select, CriteriaInterface $criteria)
    {
        if (empty($criteria->getPredicate())) {
            return;
        }

        $where = new Where();

        $table = $this->config->getTable($criteria->getEntityName());

        foreach($criteria->getPredicate() as $predicate) {
            $method = $predicate['name'];
            unset($predicate['name']);
            $predicate['attribute'] = $table . "." . $predicate['attribute'];
            call_user_func_array([$where, $method], $predicate);
        }

        $select->where($where);
    }

    private function buildOrPredicate(Select $select, CriteriaInterface $criteria)
    {
        if (empty($criteria->getOr())) {
            return;
        }

        $orWhere = new Predicate();

        foreach($criteria->getOr() as $orCriteria) {
            foreach($orCriteria->getPredicate() as $predicate) {
                $table = $this->config->getTable($orCriteria->getEntityName());

                $method = $predicate['name'];
                unset($predicate['name']);
                $predicate['attribute'] = $table . "." . $predicate['attribute'];
                call_user_func_array([$orWhere, $method], $predicate);
            }
        }

        $select->where($orWhere, Predicate::OP_OR);
    }

    private function buildRelations(Select $select, CriteriaInterface $criteria)
    {
        /** @var CriteriaInterface $relation */
        foreach ($criteria->getRelations() as $relation) {
            $table = $this->config->getTable($relation->getEntityName());

            $select->join(
                $table,
                $this->config->getRelationExpression($criteria->getEntityName(), $relation->getEntityName()),
                []
            );

            if (empty($relation->getPredicate())) {
                comtinue;
            }

            foreach($relation->getPredicate() as $predicate) {
                $method = $predicate['name'];
                unset($predicate['name']);
                $predicate['attribute'] = $table . "." . $predicate['attribute'];
                call_user_func_array([$select->where, $method], $predicate);
            }

            //$select->where->addPredicates($predicate, $combination);
        }
    }

    private function buildOrder(Select $select, CriteriaInterface $criteria)
    {
        if (empty($criteria->getOrder())) {
            return;
        }

        $table = $this->config->getTable($criteria->getEntityName());

        $order = [];
        /** @var CriteriaInterface $relation */
        foreach ($criteria->getOrder() as $order) {
            $order = $table . "." . $order;
        }

        $select->order($order);
    }

}