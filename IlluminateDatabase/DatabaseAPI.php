<?php
namespace Electro\Plugins\IlluminateDatabase;

use Illuminate\Database\Capsule\Manager;
use PhpKit\ConnectionInterface;

/**
 * An interface to the Illuminate Database API.
 *
 * ### Facades
 *
 * This plugin also emulates some common database-related Laravel facades:
 *
 * - `DB::method()`     - equivalent to `$this->query()->method()`
 * - `Schema::method()` - equivalent to `$this->schema()->method()`
 *
 * > **Note:** being an anti-pattern, facades are not recommended for development with Electro.
 * <p><br>
 * > **Note:** be sure to import the related namespaces before using the facades:
 * > - `use Electro\Plugins\IlluminateDatabase\DB;`
 * > - `use Electro\Plugins\IlluminateDatabase\Schema;`
 *
 * > **Note:** facades require a global shared context, so remember to inject an instance of this class before using any
 * of them. You don't need to use the injected instance, but just by injecting it, you'll setup the required global
 * context.
 *
 * ### Eloquent
 *
 * To use Eloquent, access your models as usual, but remember to inject an instance of this class before using any
 * model.
 *
 * ###### Ex:
 *
 * ```
 * use Electro\Plugins\IlluminateDatabase\DatabaseAPI;
 *
 * class MyClass {
 *    // you must inject an instance of DatabaseAPI before using Eloquent.
 *    function __construct (DatabaseAPI $db) {
 *      // You don't have to do anything with $db, though.
 *    }
 *    function getTheUser () {
 *      return User::find(1);
 *    }
 * }
 * ```
 */
class DatabaseAPI
{
  /**
   * The database manager instance.
   *
   * @var Manager
   */
  public $manager;

  public function __construct (ConnectionInterface $connection)
  {
    $this->manager = new Manager;
    $this->manager->addConnection ($connection->getProperties ());
  }

  /**
   * Returns an instance of the Illuminate Database connection having the speified name, or the default connection if no
   * name is given.
   *
   * @param string $connectionName [optional] A connection name, if you want to use a connection other than the default.
   * @return \Illuminate\Database\Connection
   */
  public function connection ($connectionName = null)
  {
    return $this->manager->getConnection ($connectionName);
  }

  /**
   * Checks if a table exists, even if the connection is on "pretending" mode.
   *
   * ><p>**Note:** this method also prevents its database queries from being logged by the
   * {@see \Illuminate\Database\Connection} object.<br>
   * This is required for making sure they do not become part of the generated migration/rollback SQL code.
   *
   * @param string $table          The table name.
   * @param string $connectionName [optional] A connection name, if you want to use a connection other than the
   *                               default.
   * @return bool true if the table exists.
   */
  public function hasTable ($table, $connectionName = null)
  {
    $schema     = $this->schema ($connectionName);
    $con        = $this->connection ();
    $pretending = $con->pretending ();
    if (!$pretending)
      return $schema->hasTable ($table);
    forceSetProperty ($con, 'pretending', false);
    $logging = $con->logging ();
    $con->disableQueryLog ();
    $v = $schema->hasTable ($table);
    forceSetProperty ($con, 'pretending', true);
    if ($logging)
      $con->enableQueryLog ();
    return $v;
  }

  /**
   * Returns an instance of the Illuminate Database query builder.
   *
   * @param string $connectionName [optional] A connection name, if you want to use a connection other than the default.
   * @return \Illuminate\Database\Query\Builder
   */
  public function query ($connectionName = null)
  {
    return $this->manager->getConnection ($connectionName)->query ();
  }

  /**
   * Returns an instance of the Illuminate Database schema builder.
   *
   * @param string $connectionName [optional] A connection name, if you want to use a connection other than the default.
   * @return \Illuminate\Database\Schema\Builder
   */
  public function schema ($connectionName = null)
  {
    return $this->manager->getConnection ($connectionName)->getSchemaBuilder ();
  }

  /**
   * Returns an instance of the Illuminate Database query builder, bound to a specific table.
   *
   * @param string $table          Table name,
   * @param string $connectionName [optional] A connection name, if you want to use a connection other than the default.
   * @return \Illuminate\Database\Query\Builder
   */
  public function table ($table, $connectionName = null)
  {
    return $this->query ($connectionName)->from ($table);
  }

  public function updateMultipleSelection ($pivotTable, $ownerField, $ownerValue, $valueField, array $values,
                                           $connectionName = null)
  {
    $this->connection ($connectionName)->transaction (function () use (
      $pivotTable, $ownerField, $ownerValue, $values, $valueField
    ) {
      $pivotTable = $this->table ($pivotTable);
      $pivotTable->where ($ownerField, $ownerValue)->delete ();
      foreach ($values as $v)
        $pivotTable->insert ([
          $ownerField => $ownerValue,
          $valueField => $v,
        ]);
    });
  }

  public function updateMultipleSelectionBool ($table, $field, array $selectedIDs, $pk = 'id', $connectionName = null)
  {
    $this->connection ($connectionName)->transaction (function () use ($table, $field, $selectedIDs, $pk) {
      $table = $this->table ($table);
      $table->update ([$field => 0]);
      $table->whereIn ($pk, $selectedIDs)->update ([$field => 1]);
    });
  }

}
