# Infrastructure

Master:
[![Build Status](https://travis-ci.org/t4web/Infrastructure.svg?branch=master)](https://travis-ci.org/t4web/Infrastructure)
[![codecov.io](http://codecov.io/github/t4web/Infrastructure/coverage.svg?branch=master)](http://codecov.io/github/t4web/Infrastructure?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/t4web/Infrastructure/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/t4web/Infrastructure/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda/mini.png)](https://insight.sensiolabs.com/projects/973ae246-c9a7-4a93-b84b-24fbcafd3cda)
[![Dependency Status](https://www.versioneye.com/user/projects/5639f8af1d47d400190001a6/badge.svg?style=flat)](https://www.versioneye.com/user/projects/5639f8af1d47d400190001a6)

Infrastructure layer for Domain, implementation by [t4web\domain-interface](https://github.com/t4web/DomainInterface)

## Contents
- [Installation](#instalation)
- [Quick start](#quick-start)
- [Components](#components)
- [Build criteria from array](#build-criteria-from-array)
- [Configuring](#configuring)
- [Events](#events)

## Installation

Add this project in your composer.json:

```json
"require": {
    "t4web/infrastructure": "~1.3.0"
}
```

Now tell composer to download Domain by running the command:

```bash
$ php composer.phar update
```

## Quick start

You can use `Repository` with Domain implementation [t4web\domain](https://github.com/t4web/Domain).
This implementation build on [Zend\Db](https://github.com/zendframework/zend-db) and 
[Zend\EventManager](https://github.com/zendframework/zend-eventmanager)

## Components

- `Criteria` - for creating fetch expression
  ```php
  $criteria = new T4webInfrastructure\Criteria('Task');
  $criteria->equalTo('id', 2);
  $criteria->in('type', [1,2,3]);
  $criteria->limit(20);
  $criteria->offset(10);
  $criteria->relation('Photos')
      ->equalTo('status', 3)
      ->greaterThan('created_dt', '2015-10-30');
  ```

- `CriteriaFactory` - for creating complex criteria from array
  ```php
  $criteriaFactory = new T4webInfrastructure\CriteriaFactory();
  $criteria = $criteriaFactory->build(
      'Task',
      [
          'status.equalTo' => 2,
          'dateCreate.greaterThan' => '2015-10-30',

          'relations' => [
              'User' => [
                  'status.in' => [2, 3, 4],
                  'name.like' => 'gor'
              ]
          ]
      ]
  );
  ```
  
- `Mapper` - for translate `Entity` to table row (array), and filter table row according `columnsAsAttributesMap`
  ```php
  $columnsAsAttributesMap = [
      'id' => 'id',
      'project_id' => 'projectId',
      'name' => 'name',
      'assignee_id' => 'assigneeId',
      'status' => 'status',
      'type' => 'type',
  ];
  $tableRow = [
      'id' => 22,
      'project_id' => 33,
      'name' => 'Some name',
      'assignee_id' => 44,
      'status' => 2,
      'type' => 1,
  ];
  $mapper = new T4webInfrastructure\Mapper($columnsAsAttributesMap);
  $filteredTableRow = $mapper->fromTableRow($tableRow);
  $tableRow = $mapper->toTableRow($entity);
  ```

- `QueryBuilder` - for build SQL query
  ```php
  $queryBuilder = new T4webInfrastructure\QueryBuilder();
  
  $criteria = new T4webInfrastructure\Criteria('Task');
  $criteria->equalTo('id', 2);
  $criteria->relation('Photos')
      ->equalTo('status', 3);
      
  /** @var Zend\Db\Sql\Select $select */
  $select = $queryBuilder->getSelect($criteria);
  
  $tableGateway = new Zend\Db\TableGateway\TableGateway('tasks', $dbAdapter);
  $rows = $this->tableGateway->selectWith($select);
  
  $sql = $select->getSqlString($this->dbAdapter->getPlatform());
  // $sql = SELECT `tasks`.*
  //        FROM `tasks`
  //        INNER JOIN `photos` ON `photos`.`task_id` = `tasks`.`id`
  //        WHERE `tasks`.id = 2 AND `photos`.`status` = 3
  ```

- `Repository` - for store entities and restore from storage
  ```php
  $repository = $serviceLocator->get('Task\Infrastructure\Repository');
  /** @var Tasks\Task\Task $task */
  $task = $repository->findById(123);

  $repository = $serviceLocator->get('Task\Infrastructure\FinderAggregateRepository');
  $task = $repository->findWith('User')->findById(123);
  /** @var Users\User\User $assignee */
  $assignee = $task->getAssignee();
  ```

## Build criteria from array

You can use `CriteriaFactory::build()` for building criteria from array (for example: 
from input filter, post\get request)

```php
$inputData = $_GET;

$criteriaFactory = new T4webInfrastructure\CriteriaFactory();
$criteria = $criteriaFactory->build(
    'Task',
    $inputData
);
```

`$inputData` must be structured like this:

```php
$inputData = [
     'status.equalTo' => 2,
     'dateCreate.greaterThan' => '2015-10-30',
     // ...
     'ATTRIBUTE.METHOD' => VALUE
 ]
```

where `ATTRIBUTE` - criteria field, `METHOD` - one of `equalTo`, `notEqualTo`, `lessThan`,
`greaterThan`, `greaterThanOrEqualTo`, `lessThanOrEqualTo`, `like`, `in`

for `isNull`, `isNotNull` use
 
```php
$inputData = [
  'ATTRIBUTE.isNull' => TRUE_EXPRESSION,
  'ATTRIBUTE.isNotNull' => TRUE_EXPRESSION,
  
  // example
  'status.isNull' => true,
  'dateCreate.isNotNull' => 1,
]
```
 
where `TRUE_EXPRESSION` can be any true expression: `true`, `1`, `'a'` etc.
 
for `between` use array as value
 
```php
$inputData = [
   'ATTRIBUTE.between' => [MIN_VALUE, MAX_VALUE],
   
   // example
   'dateCreate.between' => ['2015-10-01', '2015-11-01'],
]
```
  
for `limit`, `offset` use 

```php
$inputData = [
   'limit' => VALUE,
   'offset' => VALUE,
   
   // example
   'limit' => 20,
   'offset' => 10,
]
```

for `order` use SQL-like order expression

```php
$inputData = [
   'order' => EXPRESSION,
   
   // example
   'order' => 'dateCreate DESC',
   'order' => 'dateCreate DESC, status ASC',
]
```

Custom criteria - grouping and reusing criteria
```php
$inputData = [
    'Users\User\Criteria\Active' => true,
]
```
`Users\User\Criteria\Active` - must be invokable class (`__invoke(CriteriaInterface $criteria, $value)`)

## Configuring

For configuring `Repository` you must specify config, and use `Config` object for parsing config. `QueryBuilder` 
use `Config` for building SQL query.

```php
$entityMapConfig = [
    // Entity name
    'Task' => [
        
        // table name
        'table' => 'tasks',

        // use for short namespace
        'entityClass' => 'Tasks\Task\Task',
        
        // map for entity attribute <=> table fields
        'columnsAsAttributesMap' => [
            
            // attribute => table field
            'id' => 'id',
            'project_id' => 'projectId',
            'name' => 'name',
            'assignee_id' => 'assigneeId',
            'status' => 'status',
            'type' => 'type',
            'extras' => 'extras',
        ],
        
        // foreign relation
        'relations' => [
        
            // relation entity name + table.field for building JOIN
            // name => [FK in cur. entity, PK in related entity]
            'User' => ['tasks.assignee_id', 'user.id'],
            
            // relation entity name + table.field for building JOIN
            // name => [link-tabe, link-table field for cur. entity, link-table field for related entity]
            'Tag' => ['tasks_tags_link', 'task_id', 'tag_id'],
        ],

        // for aliasing long\ugly criterias
        'criteriaMap' => [
            // alias => criteria
            'date_more' => 'dateCreate.greaterThan',
        ],
        
        // for serializing persisting data 
        'serializedColumns' => [
            'extras' => 'json',
        ],
    ],
]
```

`relations` argument order - very important, `Task['relations']['User'][0]` - must be field from current entity, `Task['relations']['User'][1]` - must be field from related entity.

For many-to-many relation related entity must contain 3 arguments. For example we have table `tasks` (see config), table `tags` with fields `id`, `name` and table `tasks_tags_link` with fields `task_id` and `tag_id`. For relation Task <=> Tag we must describe many-to-many relation: `Task['relations']['Tag'][0]` - link-table name, `Task['relations']['Tag'][1]` - field PK current entity in link-table and `Task['relations']['Tag'][2]` - field PK in related entity in link-table.

## Events

`Repository` rise events when entity created or updated.
```php
$eventManager = new EventManager();
$eventManager->getSharedManager()->attach(
     REPOSITORY_IDENTIFIER,
     'entity:ENTITY_CLASS:changed',
     function(T4webInfrastructure\Event\EntityChangedEvent $e){
        $changedEntity = $e->getChangedEntity();
        $originalEntity = $e->getOriginalEntity();
        // ...
     },
     $priority
);
```

Where `REPOSITORY_IDENTIFIER` - depends from entity, builds: EntityName\Infrastructure\Repository  
`ENTITY_CLASS` - get_class() from you $enity object

Now `Repository` can rise:
- `entity:ModuleName\EntityName\EntityName:created` - rise after Entity just created in DB.
  In context event subscriber receive Zend\EventManager\Event.
- `entity:ModuleName\EntityName\EntityName:changed:pre` - rise before Entity update in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
- `entity:ModuleName\EntityName\EntityName:changed` - rise after Entity just updated in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
- `attribute:ModuleName\EntityName\EntityName:attribute:changed` - rise after Entity attribute updated in DB.
  In context event subscriber receive T4webInfrastructure\Event\EntityChangedEvent.
