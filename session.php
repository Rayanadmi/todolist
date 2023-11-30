<?php

session_start();
if (!isset($_SESSION['utilisateur']) && !isset($_SESSION['id_user'])) {
    header("Location: index.php");
}

$dbname = 'to_do_list';
$dbhost = 'localhost';
$dbuser = 'greta';
$dbpass = 'Greta1234!';

try {
    $dsn = "mysql:dbname=".$dbname.";host=".$dbhost;
    $db = new PDO($dsn, $dbuser, $dbpass);
    $db->exec("SET NAMES utf8");
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die($e->getMessage());
}

// Supprimer une tâche
if (isset($_POST['confirmer_suppression']) && !empty($_POST['confirmer_suppression'])) {
    $tacheId = $_POST['confirmer_suppression'];

    // Supprimer la tâche de la base de données
    $sqlDelete = "DELETE FROM `tache` WHERE `id` = :tacheId AND `id_user` = :id_user";
    $queryDelete = $db->prepare($sqlDelete);
    $queryDelete->bindValue(":tacheId", $tacheId, PDO::PARAM_INT);
    $queryDelete->bindValue(":id_user", $_SESSION['id_user'], PDO::PARAM_INT);
    $queryDelete->execute();
}

if (isset($_POST['stats']) && !empty($_POST['stats'])) {
    $tacheId = $_POST['stats'];
    $nouveauStatut = '';  // Initialisez cette variable avec le nouveau statut

    // Trouvez le statut actuel de la tâche dans la base de données
    $sqlStatutActuel = "SELECT `statut` FROM `tache` WHERE `id` = :tacheId AND `id_user` = :id_user";
    $queryStatutActuel = $db->prepare($sqlStatutActuel);
    $queryStatutActuel->bindValue(":tacheId", $tacheId, PDO::PARAM_INT);
    $queryStatutActuel->bindValue(":id_user", $_SESSION['id_user'], PDO::PARAM_INT);
    $queryStatutActuel->execute();
    $ancienStatut = $queryStatutActuel->fetchColumn();
    // var_dump($ancienStatut);
    // Déterminez le nouveau statut en fonction de l'ancien statut
    if ($ancienStatut == 'A faire') {
        $nouveauStatut = 'En cours';
    } elseif ($ancienStatut == 'En cours') {
        $nouveauStatut = 'Terminée';
    } else {
        $nouveauStatut = 'A faire';
    }

    // Mettez à jour le statut de la tâche dans la base de données
    $sqlSauvegarde = "UPDATE `tache` SET `statut`= :nouveauStatut WHERE `id` = :tacheId AND `id_user` = :id_user";
    $querySauvegarde = $db->prepare($sqlSauvegarde);
    $querySauvegarde->bindValue(":nouveauStatut", $nouveauStatut, PDO::PARAM_STR);
    $querySauvegarde->bindValue(":tacheId", $tacheId, PDO::PARAM_INT);
    $querySauvegarde->bindValue(":id_user", $_SESSION['id_user'], PDO::PARAM_INT);
    $querySauvegarde->execute();
}

if (isset($_POST['tache']) && !empty($_POST['tache'])) {
    $sql = "INSERT INTO `tache` (`id_user`,`taches`) VALUES (:id_user,:taches)";
    $query = $db->prepare($sql);

    if ($query) {
        $query->bindValue(":id_user", $_SESSION['id_user'], PDO::PARAM_STR);
        $query->bindValue(":taches", $_POST['tache'], PDO::PARAM_STR);
        $query->execute();
    }
}

$userId = $_SESSION['id_user'];

$sql = "SELECT COUNT(*) FROM `tache` WHERE `id_user` = :id_user";
$query = $db->prepare($sql);
$query->bindValue(":id_user", $userId, PDO::PARAM_INT);
$query->execute();

// Utilisez fetchColumn pour obtenir la valeur du COUNT
$nombreTaches = $query->fetchColumn();

// Si vous avez besoin de récupérer les détails des tâches, vous pouvez ajouter une requête supplémentaire
$sqlTaches = "SELECT * FROM `tache` WHERE `id_user` = :id_user";
$queryTaches = $db->prepare($sqlTaches);
$queryTaches->bindValue(":id_user", $userId, PDO::PARAM_INT);
$queryTaches->execute();
$taches = $queryTaches->fetchAll();

// Afficher les tâches et les supprimer
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@0,700;1,600;1,700&family=Josefin+Slab:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Noto+Sans+Kawi:wght@400;500&display=swap" rel="stylesheet">
    <script src="scrippt.js" defer></script>
    <link rel="stylesheet" href="style.css">
    <title>Votre liste de tâches, <?php echo $_SESSION["utilisateur"];?></title>
</head>
<body>
    <audio class="son" src="audio/rickroll.mp3"> </audio>
    
<div class='fondchecklist'>
    <div class='titre'>
    <img src="images/titretodo.png">
    </div>
    <div class='liste'>
    <?php 
    echo '<h1> Bonjour ' . $_SESSION["utilisateur"] . '</h1>';
    echo "Nombre de tâches pour " .$_SESSION['utilisateur']. ' : ' . $nombreTaches;

    foreach ($taches as $tache) {
        echo '<div class="ajouttaches">' . htmlspecialchars($tache['taches']) .
            '<form action="" method="post" class="formtache" >
            <button class="statut" type="submit" name="stats" value="'.$tache["id"].'">'.$tache['statut'].'</button>

            <button class="btn btntaches" type="submit" name="confirmer_suppression" value='.$tache["id"].' >
            Supprimer</button>
            
            </form></div>';

    }
    //<div class="statut" name="stats" data-id="'.$tache["id"].'">'.$tache['statut'].'</div>

    // <button class="btn btntache statut btnSauvegarde" type="submit" name="Sauvegarder" value='.$tache["id"].'>
    //         Sauvegarder</button>
    
?>    
<form action="" method="post" class="babache">
    <input type="text" placeholder="Entrez votre tâche" name="tache">
    <button type="submit" class="btn btntache" name="ajouterLaTache">Ajouter la tâche</button>
</form>

</div>
<form action="deconnexion.php" method="post">
    <button class="btn btndisconnect">Se déconnecter</button>
</form>
</div>

<p class="textsignature "> Tout droit réservé Admi Rayan et Carette Axel . </p>

</body>
</html>
