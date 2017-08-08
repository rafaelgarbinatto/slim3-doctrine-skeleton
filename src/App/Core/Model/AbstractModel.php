<?php
/**
 * Created by PhpStorm.
 * User: ng
 * Date: 13/06/17
 * Time: 13:54
 */

namespace App\Core\Model;

use App\Core\Traits\Helpers\ObjectHelpers;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid as Uuid;
use GeneratedHydrator\Configuration;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\ORM\EntityManager as EntityManager;
use Doctrine\ORM\EntityRepository;

Abstract Class AbstractModel implements IModel
{
    use ObjectHelpers;

    const LIMIT  = 10;
    const OFFSET = 0;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * Entity namespace default for this model
     */
    public $entity_name;

    /**
     * @var Array data
     *
     */

    protected $data = array();

    /**
     * AbstractModel constructor.
     * @param EntityManager $entityManager
     */
    public function __construct( EntityManager	$entityManager )
    {
        $this->entityManager = $entityManager;
        $this->entity_name = $this->repository->getClassName();
    }

    /**
     * @param Object $data
     *
     * return Object
     */
    public function create(Array $data)
    {
        try
        {
            $this->data = $data;

            $mapperClass = new \ReflectionClass($this->entity_name);
            $obj = $mapperClass->newInstance();
            $obj = $this->populateAssociation($obj);
            $obj = $this->populateObject($obj);

            return  $this->repository->save($obj);
        }
        catch (PDOException $e)
        {
            throw new PDOException($e->getMessage() . " (AMD0001exc)");
        }
    }

    /**
     * @param  Array $data
     * @return Object | NULL
     * @throws \Exception
     */
    public function update(Array $data)
    {
        try{
            if ( ! isset($data['id']) || ! Uuid::isValid($data['id']) )
            {
                throw new \InvalidArgumentException("'Id' value is not set or is invalid (ACCENT013exc)");
            }


            $obj = $this->repository->findOneById($data['id']);
            if( ! $obj instanceof $this->entity_name )
            {
                return null;
            }

            $obj = $this->repository->save($this->populateObject($obj, $data)); // TODO: Change the autogenerated stub

            return $obj;
        }
        catch (\Exception $e)
        {
            throw new PDOException($e->getMessage() . " (AMD0002exc)");
        }
    }

    /**
     * @param Integer $id
     *
     * @return Boolean
     */
    public function remove($id)
    {
        try
        {
            $res = $this->repository->remove($id);
            return $res;
        }
        catch (PDOException $e)
        {
            throw new PDOException($e->getMessage() . " (AMD0003exc)");
        }
    }

    public function findAll(Array $data)
    {
        $data['filters'] = (isset($data['filters']) && is_array($data['filters'])) ? $data['filters'] : array();
        $data['order']   = (isset($data['order']) && is_array($data['order']))     ? $data['order']   : array();
        $data['limit']   = (isset($data['limit']) && is_numeric($data['limit']))   ? $data['limit']   : self::LIMIT;
        $data['offset']  = (isset($data['offset']) && is_numeric($data['offset'])) ? $data['offset']  : self::OFFSET;

        try
        {
            $arrObjs = $this->repository->getSimpleListBy($data['filters'], $data['order'], $data['limit'], $data['start']);
            $res = array();
            if (count($arrObjs) > 0)
            {
                foreach ($arrObjs as $obj)
                {
                    $res[]=  $obj->toArray(null, array('__cloner__', '__isInitialized__', '__initializer__'));
                }

            }
            return $res;
        }
        catch(\PDOException $e)
        {
            throw $e;
        }
    }

    public function populateObject($obj)
    {
        try {

            if ($obj->getId())
            {
                $objData = $obj->toArray();
                $this->data = array_filter(array_merge($objData, $this->data));
            }

            $config = new Configuration($this->entity_name);
            $hydratorClass = $config->createFactory()->getHydratorClass();
            $hydrator = new $hydratorClass();
            $hydrator->hydrate($this->data, $obj);

            return $obj;
        }catch(\Exception $e){
            throw $e;
        }
    }

    protected function populateAssociation($obj)
    {
        try {
            $metaData = $this->entityManager->getClassMetadata($this->entity_name);

            foreach($this->data as $attr => $value)
            {
                if($metaData->hasAssociation($attr))
                {
                    $association = $metaData->getAssociationMapping($attr);
                    $assocAttr = array_keys($association['targetToSourceKeyColumns']);

                    if( $metaData->isAssociationWithSingleJoinColumn($attr) ){
                        $this->data[$attr] =  $this->entityManager->getRepository($association['targetEntity'])
                            ->findOneBy(array($assocAttr[0] => $value));
                    }else{
                        $this->data[$attr] =  new ArrayCollection($this->entityManager->getRepository($association['targetEntity'])
                            ->findBy(array($assocAttr[0] => $value)));
                    }

                }
            }

            return $obj;

        }catch(\Exception $e){
            throw $e;
        }

    }

    public function extractObject($obj)
    {
        $config = new Configuration($this->entity_name);
        $hydratorClass = $config->createFactory()->getHydratorClass();
        $hydrator = new $hydratorClass();

        return $hydrator->extract($obj);
    }

    /**
     * @param $repository
     * @return $this
     */
    public function setRepository(EntityRepository $repository)
    {
        $this->repository = $repository;

        return $this;
    }

}