#!/bin/bash

interface=org.asamk.Signal
member=ActiveChanged

dbus-monitor --profile "interface='$interface',member='$member'" |
while read -r line; do
    echo $line | grep ActiveChanged && your_script_goes_here
done

dbus-monitor "interface='org.asamk.Signal',member='MessageReceivedV2'"|
while read -r line; do
    echo $line | grep MessageReceivedV2 && echo MessageReceivedV2
done

dbus-monitor --profile "interface='org.asamk.Signal',member='MessageReceivedV2'"|
while read line; do
    php /home/simnige1/web/simnigeria.org/public_html/wp-content/plugins/sim-plugin/includes/modules/signal/daemon/daemon2.php $line
done