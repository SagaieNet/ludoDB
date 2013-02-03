<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'accessortest' => '/Tests/AccessorTest.php',
                'book' => '/examples/mod_rewrite/Book.php',
                'brand' => '/Tests/classes/Brand.php',
                'cachetest' => '/Tests/CacheTest.php',
                'capital' => '/Tests/classes/JSONCaching/Capital.php',
                'capitals' => '/Tests/classes/JSONCaching/Capitals.php',
                'car' => '/Tests/classes/Car.php',
                'carcollection' => '/Tests/classes/CarCollection.php',
                'carproperties' => '/Tests/classes/CarProperties.php',
                'carproperty' => '/Tests/classes/CarProperty.php',
                'city' => '/Tests/classes/City.php',
                'client' => '/Tests/classes/Client.php',
                'collectiontest' => '/Tests/CollectionTest.php',
                'columnaliastest' => '/Tests/ColumnAliasTest.php',
                'configparsertest' => '/Tests/ConfigParserTest.php',
                'configparsertestjson' => '/Tests/ConfigParserTestJSON.php',
                'country' => '/Tests/classes/Country.php',
                'findertest' => '/Tests/FinderTest.php',
                'forsqltest' => '/Tests/classes/ForSQLTest.php',
                'jsontest' => '/Tests/JSONTest.php',
                'ludodb' => '/LudoDB.php',
                'ludodbadapter' => '/LudoDBInterfaces.php',
                'ludodbcache' => '/LudoDBCache.php',
                'ludodbclassnotfoundexception' => '/LudoDBExceptions.php',
                'ludodbcollection' => '/LudoDBCollection.php',
                'ludodbcollectionconfigparser' => '/LudoDBCollectionConfigParser.php',
                'ludodbconfigparser' => '/LudoDBConfigParser.php',
                'ludodbconnectionexception' => '/LudoDBExceptions.php',
                'ludodbexception' => '/LudoDBExceptions.php',
                'ludodbiterator' => '/LudoDBIterator.php',
                'ludodbmodel' => '/LudoDBModel.php',
                'ludodbmodeltests' => '/Tests/LudoDBModelTests.php',
                'ludodbmysql' => '/LudoDBMysql.php',
                'ludodbmysqli' => '/LudoDBMySqlI.php',
                'ludodbobject' => '/LudoDBObject.php',
                'ludodbobjectnotfoundexception' => '/LudoDBExceptions.php',
                'ludodbpdo' => '/LudoDBPDO.php',
                'ludodbregistry' => '/LudoDBRegistry.php',
                'ludodbrequesthandler' => '/LudoDBRequestHandler.php',
                'ludodbservice' => '/LudoDBInterfaces.php',
                'ludodbunauthorizedexception' => '/LudoDBExceptions.php',
                'ludosql' => '/LudoSQL.php',
                'manager' => '/Tests/classes/Manager.php',
                'mysqlitests' => '/Tests/MysqlITests.php',
                'objectcreatortest' => '/Tests/ObjectCreatorTest.php',
                'pdotests' => '/Tests/PDOTests.php',
                'people' => '/Tests/classes/People.php',
                'peopleplain' => '/Tests/classes/PeoplePlain.php',
                'performancetest' => '/Tests/PerformanceTest.php',
                'person' => '/Tests/classes/Person.php',
                'personforconfigparser' => '/Tests/classes/PersonForConfigParser.php',
                'phone' => '/Tests/classes/Phone.php',
                'phonecollection' => '/Tests/classes/PhoneCollection.php',
                'requesthandlermock' => '/Tests/classes/RequestHandlerMock.php',
                'requesthandlertest' => '/Tests/RequestHandlerTest.php',
                'section' => '/Tests/classes/Section.php',
                'sqltest' => '/Tests/SQLTest.php',
                'testbase' => '/Tests/TestBase.php',
                'testgame' => '/Tests/classes/TestGame.php',
                'testtable' => '/Tests/classes/TestTable.php',
                'testtimer' => '/Tests/classes/TestTimer.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);
// @codeCoverageIgnoreEnd