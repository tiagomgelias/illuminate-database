<?php
use Electro\Plugins\IlluminateDatabase\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class __CLASS__ extends AbstractMigration
{
  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();

    // Pick one of the alternatives below (and delete the other one):

    // Create a new table
    $schema->create ('tableName', function (Blueprint $table) {
      $table->increments ('id');
      $table->timestamps ();
      // More columns...
    });

    // Or

    // Modify an existing table
    $schema->table ('tableName', function (Blueprint $table) {
      // Your code here
    });
  }

  /**
   * Reverse the migration.
   *
   * @return void
   */
  function down ()
  {
    $schema = $this->db->schema ();

    // Pick one of the alternatives below (and delete the other one):

    // Drop the table created on up()
    if ($schema->hasTable ('tableName'))
      $schema->drop ('tableName');

    // Or

    // Undo the modifications made to the table on up()
    $schema->table ('tableName', function (Blueprint $table) {
      // Your code here
    });
  }

}
