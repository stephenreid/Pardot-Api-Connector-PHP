<?php
/**
 * Update Prospect Test
 * This is a basic action to populate a prospect object
 * Then we can use magical getters and setters
 * to update the data array.
 * This should work on default and custom fields.
 * LAST TESTED 6/6/2012
 */
include('./PardotConnector.class.php');
include('./Prospect.class.php');
$p = new Prospect();
$p = $p->fetchProspectByEmail('test@test.com');
$p->email = 'test+11@test.com';
$p->save();
$p->email = 'test@test.com';
$p->save();



$connector = new PardotConnector();
$connector->authenticate();

//Test our basic method
$prospect = $connector->prospectUpsert(array('email'=>'test@test.com'));
$prospect = $connector->read('prospect',array('email'=>'test@test.com'));
$prospect = $connector->prospectRead(array('email'=>'test@test.com'));

//test account getter (no param)
$account = $connector->accountRead();
//basic method
$campaigns = $connector->query('campaign',array('updated_after'=>'last year'));
//fluid method
$connector->campaignQuery();
$connector->formQuery();

