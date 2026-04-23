<?php
session_start();
// On détruit toutes les variables de session
session_destroy();
// On redirige vers l'accueil ou le login
header("Location: login.php");
exit();