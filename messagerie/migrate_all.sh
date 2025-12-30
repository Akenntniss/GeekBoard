#!/bin/bash
DOMAINS=("samitest.servo.tools" "phone-tik.servo.tools" "mdgkl.servo.tools" "dsaads.servo.tools" "mdgkl2.servo.tools" "klover2.servo.tools" "moiuch.servo.tools")

for domain in "${DOMAINS[@]}"; do
    echo "Running migration for $domain..."
    # Utiliser -L pour suivre les redirections si nécessaire, et -k pour ignorer SSL si on tape en https (mais ici on tape 127.0.0.1:80)
    # On tape sur 127.0.0.1 pour éviter le DNS lookup, nginx routera via le Host header
    curl -s -L -H "Host: $domain" http://127.0.0.1/messagerie/setup_signatures_db.php
    echo -e "\n----------------------------------------"
done
