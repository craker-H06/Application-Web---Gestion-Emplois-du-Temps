<?php
// generate_hash.php je dois supprimer après usage awalou $plain = 'jesuissudais'; // <-- remplace par le mot de passe en clair que TU veux
// echo password_hash($plain, PASSWORD_DEFAULT);

echo password_hash("sudais", PASSWORD_DEFAULT);

