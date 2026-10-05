#!/bin/sh
for s in nginx dovecot postfix; do systemctl is-active --quiet $s && systemctl reload $s; done
