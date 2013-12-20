#!/bin/bash

#Readme: Disable line 20 in migration file 20131203172054
#Run the migration
#Run this script
#Reenable line 20 from migration file

db="campsite_production";
host="campprod.cbbzzx1cx8wk.us-west-2.rds.amazonaws.com"
user="campsitemaster"
pass="e71243580e"

#Make sure our row counter is set to 1
echo "use $db;";
echo "ALTER TABLE entry_set_registry AUTO_INCREMENT = 1;";
registryId=1;

#Create entrySetRegistry instances for each site and connect them together
sites=`mysql -N -B -u$user -p$pass -h $host -e "SELECT id FROM pd_site" $db`;
while read -r siteId; do
    echo "INSERT INTO entry_set_registry (scope, containerId) VALUES ('SpoutletBundle:Site', $siteId);"
    echo "UPDATE pd_site SET entrySetRegistration_id=$registryId WHERE id=$siteId;"
    let registryId++;
done <<< "$sites"

#Create entrySetRegistry instances for each group and connect them together
groups=`mysql -N -B -u$user -p$pass -h $host -e "SELECT id FROM pd_groups" $db`;
while read -r groupId; do
    echo "INSERT INTO entry_set_registry (scope, containerId) VALUES ('GroupBundle:Group', $groupId);"
    echo "UPDATE pd_groups SET entrySetRegistration_id=$registryId WHERE id=$groupId;"
    let registryId++;
done <<< "$groups"

#Create entrySetRegistry instances for each event and connect them together
events=`mysql -N -B -u$user -p$pass -h $host -e "SELECT id FROM group_event" $db`;
while read -r eventId; do
    echo "INSERT INTO entry_set_registry (scope, containerId) VALUES ('EventBundle:GroupEvent', $eventId);"
    echo "UPDATE group_event SET entrySetRegistration_id=$registryId WHERE id=$eventId;"
    let registryId++;
done <<< "$events"

#Copy relevant fields from event over to entrySet then remove the associated event fields
echo "INSERT INTO entry_set (entrySetRegistration_id, name, type, isVotingActive, isSubmissionActive, allowedVoters) SELECT g.entrySetRegistration_id, 'Proposed Ideas', g.type, g.isVotingActive, g.isSubmissionActive, g.allowedVoters FROM group_event as g;"

echo "ALTER TABLE group_event DROP isVotingActive, DROP isSubmissionActive, DROP allowedVoters, DROP type;"

#Set the correct entrySet id for each idea
#Get ideas and event ids
ideas=`mysql -N -B -u$user -p$pass -h $host -e "SELECT id FROM idea" $db`;
while read -r ideaId; do
    echo "UPDATE idea SET entrySet_id=( SELECT list.id FROM (group_event as event join entry_set as list) WHERE event.entrySetRegistration_id = list.entrySetRegistration_id AND event.id = idea.entrySet_id ) WHERE id=$ideaId;"
done <<< "$ideas"

