<?php

$section = $this->menuSection(N_('IcingaDB'))
    ->setIcon('gauge')
    ->setPriority(45);

$section->add(N_('Hosts'))
    ->setUrl('icingadb/hosts')
    ->setPermission('icingadb/hosts')
    ->setPriority(31);
$section->add(N_('Services'))
    ->setUrl('icingadb/services')
    ->setPermission('icingadb/services')
    ->setPriority(32);
$section->add(N_('Hostgroups'))
    ->setUrl('icingadb/hostgroups')
    ->setPermission('icingadb/hostgroups')
    ->setPriority(33);
$section->add(N_('Environments'))
    ->setUrl('icingadb/environments')
    ->setPermission('icingadb/environments')
    ->setPriority(30);
