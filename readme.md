Framework for easy creation and manipulation of mySQL tables using PHP.

Example:

	<?php

		class Person extends LudoDBModel
		{
			protected $idField = 'id';
			protected $config = array(
				'table' => 'Person',
				'columns' => array(
					'id' => 'int auto_increment not null primary key',
					'firstname' => 'varchar(32)',
					'lastname' => 'varchar(32)',
					'address' => 'varchar(64)',
					'zip' => 'varchar(5)'
				),
				'join' => array(
					array('table' => 'city', 'pk' => 'zip', 'fk' => 'zip', 'columns' => array('city'))
				)

			);

			public function __construct($id){
			 parent::__construct($id);
			}

			public function setFirstname($value){
				$this->setValue('firstname', $value);
			}

			public function setLastname($value){
				$this->setvalue('lastname', $value);
			}

			public function setZip($value){
				$this->setValue('zip', $value);
			}

			public function getFirstname(){
				return $this->getValue('firstname');
			}

			public function getLastname(){
				return $this->getValue('lastname');
			}

			public function getZip(){
				return $this->getValue('zip');
			}

			public function getCity(){
				return $this->getValue('city');
			}
		}


	?>
Usage:

	<?php
	$person = new Person();
	$person->setFirstname('John');
	$person->setLastname('Wayne');
	$person->commit();
	?>
For creating a new Person record and save it to the database

	<?php
	echo $person->getId();
	echo $person->getFirstname();
	echo $person->getLastname();
	?>

Will output data for this record.

	<?php
	$person = new Person(1);
	$person->setLastname('Johnson');
	$person->commit();
	?>
Will update lastname in db for person with id=1

	<?php
	echo $person;
	?>

will output person data in JSON format.

You can also configure the database in json files:

Example

PHP Class (Client.php)

	<?php
	class Client extends LudoDBModel
	{
		protected $JSONConfig = true;

		public function __construct($id){
		 parent::__construct($id);
		}

	}

JSON file(Client.json) located in sub folder JSONConfig:

	{
		"table":"Client",
		"idField":"id",
		"constructBy":"id",
		"columns":{
			"id":"int auto_increment not null primary key",
			"firstname":{
				"db": "varchar(32)",
				"access":"rw"
			},
			"lastname":{
				"db": "varchar(32)",
				"access": "rw"
			},
			"address":{
				"db": "varchar(64)",
				"access": "rw"
			},
			"zip":{
				"db": "varchar(5)",
				"access": "rw"
			},
			"phone":{
				"class":"PhoneCollection"
			},
			"city":{
				"class":"City",
				"get":"getCity"
			}

		},
		"classes":{
			"city":{
				"fk":"zip"
			}
		}
	}

Which gives you automatic setters and getters for lastname, firstname, address and zip