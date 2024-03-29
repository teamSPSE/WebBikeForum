<?php

/**
 * Trida spravujici databazi.
 */
class DatabaseModel {

    /** @var PDO $pdo  Objekt pracujici s databazi prostrednictvim PDO. */
    private $pdo;

    /**
     * Inicializace pripojeni k databazi.
     */
    public function __construct() {
        // inicializace DB
        $this->pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME, DB_USER, DB_PASS);
        // vynuceni kodovani UTF-8
        $this->pdo->exec("set names utf8");
    }
/*
    public function testPrepered(){
        $iduzivatele = 1;

        $sql = "SELECT iduzivatele, jmeno FROM uzivatele WHERE iduzivatele = :iduzivatele";
        $s = $this->pdo->prepare($sql);
        $s->bindValue(":iduzivatele", 1);

        $s->execute();
        return $s->fetchAll();
    }
*/
    public function getClanky(int $idclanky = -1, int $schvaleno = -1, int $hodnoceno = -1, int $iduzivatele = -1, int $idrecenzent = -1):array {
        // pripravim dotaz
        $orderBy= "ORDER BY ";
        $orderBy .= TABLE_CLANKY.".datumcas_vlozeni desc";


        if($schvaleno!=-1){
            $where[] = TABLE_CLANKY.".schvaleno = :schvaleno";
        }
        if($idclanky!=-1){
            $where[] = TABLE_CLANKY.".idclanky=:idclanky";
        }
        if($hodnoceno == 0){
            $where[] = TABLE_HODNOCENI.".idclanky is null";
        }
        if($iduzivatele != -1){
            $where[] = TABLE_CLANKY.".iduzivatele = :iduzivatele";
        }
        if($idrecenzent != -1){
            $where[] = TABLE_HODNOCENI.".iduzivatele = :idrecenzent";
        }
        if(!empty($where))
            $whereSQL = "WHERE ".join(" and ",$where);

        $sql = "SELECT ".TABLE_CLANKY.".idclanky,
                        ".TABLE_CLANKY.".titulek,
                        ".TABLE_CLANKY.".datumcas_vlozeni,
                        ".TABLE_CLANKY.".iduzivatele as autor,
                        ".TABLE_CLANKY.".obrazek,
                        ".TABLE_CLANKY.".schvaleno,
                        ".TABLE_CLANKY.".text
                  FROM ".TABLE_CLANKY." 
                      left join ".TABLE_HODNOCENI."
                        on ".TABLE_CLANKY.".idclanky = ".TABLE_HODNOCENI.".idclanky 
                  $whereSQL      
                  $orderBy";
        //echo $sql;


        $s = $this->pdo->prepare($sql);

        if($schvaleno!=-1){
            $s->bindValue(":schvaleno", $schvaleno);
        }
        if($idclanky!=-1){
            $s->bindValue(":idclanky", $idclanky);
        }
        if($iduzivatele != -1){
            $s->bindValue(":iduzivatele", $iduzivatele);
        }
        if($idrecenzent != -1){
            $s->bindValue(":idrecenzent", $idrecenzent);
        }



        $s->execute();
        return $s->fetchAll();
    }

    public function getHodnoceniByIdclanky(int $idclanky){
        $sql = "SELECT ".TABLE_HODNOCENI.".komentar,
                        ".TABLE_HODNOCENI.".pocet_hvezd,
                        ".TABLE_HODNOCENI.".idhodnoceni,
                        ".TABLE_UZIVATELE.".jmeno
                FROM ".TABLE_HODNOCENI." 
                    left join ".TABLE_UZIVATELE."
                        on ".TABLE_HODNOCENI.".iduzivatele = ".TABLE_UZIVATELE.".iduzivatele
                WHERE ".TABLE_HODNOCENI.".idclanky=:idclanky and ".TABLE_HODNOCENI.".hodnoceno = 1
                ORDER BY ".TABLE_HODNOCENI.".datumcas_vlozeni desc";

        $s = $this->pdo->prepare($sql);
        $s->bindValue(":idclanky", $idclanky);
        $s->execute();
        return $s->fetchAll();
    }

    public function updateRecenze(int $pocet_hvezd, string $komentar, int $idclanky, int $iduzivatele){
        $dnesni_datum = date("Y-m-d H:i:s");
        $sql = "UPDATE ".TABLE_HODNOCENI." SET  pocet_hvezd=:pocet_hvezd, 
                                                datumcas_vlozeni=:datumcas_vlozeni, 
                                                hodnoceno= 1, 
                                                komentar=:komentar
                WHERE   idclanky=:idclanky 
                    and iduzivatele=:iduzivatele;";

        //echo $sql;
        $s = $this->pdo->prepare($sql);
        $s->bindValue(":pocet_hvezd", $pocet_hvezd);
        $s->bindValue(":datumcas_vlozeni", $dnesni_datum);
        $s->bindValue(":komentar", $komentar);
        $s->bindValue(":idclanky", $idclanky);
        $s->bindValue(":iduzivatele", $iduzivatele);
        return $s->execute();
/*
        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }*/
    }











    public function insertNewUser(string $jmeno, string $heslo, string $email, string $login){

        if( empty($this->getIduzivateleByLogin($login)) ){
            $sql = "INSERT INTO " . TABLE_UZIVATELE . " (jmeno, heslo, login, email) VALUES ('$jmeno', '$heslo', '$login', '$email')";
            if($this->pdo->exec($sql) === false){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }

    }

    public function getHesloByLogin(string $login){
        $sql = "SELECT heslo FROM ".TABLE_UZIVATELE." WHERE login = '$login';";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getPrava(){
        $sql = "SELECT idprava, pravo, popis FROM ".TABLE_PRAVA;
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getPravaByLogin(string $login){
        $sql = "SELECT  ".TABLE_PRAVA.".idprava, 
                        ".TABLE_PRAVA.".pravo, 
                        ".TABLE_PRAVA.".popis 
                FROM ".TABLE_UZIVPRAVA." 
                    left join ".TABLE_UZIVATELE."
                        on ".TABLE_UZIVPRAVA.".iduzivatele = ".TABLE_UZIVATELE.".iduzivatele
                    left join ".TABLE_PRAVA."
                        on ".TABLE_UZIVPRAVA.".idprava = ".TABLE_PRAVA.".idprava
                WHERE ".TABLE_UZIVATELE.".login = '$login'";

        //echo $sql;
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getUzivateleInfo(int $iduzivatele = 0){
        $uzivatele_return = array();

        $sql = "SELECT ".TABLE_UZIVATELE.".iduzivatele,
                      ".TABLE_UZIVATELE.".jmeno,
                       ".TABLE_UZIVATELE.".login,
                       ".TABLE_UZIVATELE.".email
                FROM ".TABLE_UZIVATELE;
        if(!empty($iduzivatele)){
            $sql.= " WHERE ".TABLE_UZIVATELE.".iduzivatele = '$iduzivatele'";
        }

        $sql.= " ORDER BY ".TABLE_UZIVATELE.".iduzivatele";

        //echo $sql;
        $uzivatele = $this->pdo->query($sql)->fetchAll();

        if(!empty($uzivatele)){
            foreach ($uzivatele as $uzivatel){
                $prava = $this->getPravaByLogin($uzivatel["login"]);
                $prava_uzivatele = "";
                if(!empty($prava)){
                    foreach ($prava as $pravo){
                        $prava_uzivatele .= $pravo["pravo"]."(".$pravo["idprava"].") ";
                    }
                }
                $uzivatel["prava"] = $prava_uzivatele;
                $uzivatele_return[] = $uzivatel;
            }
        }

        return $uzivatele_return;
    }

    public function setPravo(int $iduzivatele, int $idprava, int $hodnota){
        $jak = true;

        $sql = "DELETE FROM ".TABLE_UZIVPRAVA." WHERE idprava='$idprava' and iduzivatele='$iduzivatele'";
        if($this->pdo->exec($sql) === false){
            $jak = false;
        }else{
            $jak = true;
        }

        if($hodnota == 1){
            $sqlInsert= "INSERT INTO ".TABLE_UZIVPRAVA." (idprava, iduzivatele) VALUES ('$idprava', '$iduzivatele') 
                            on DUPLICATE key update
                                idprava = '$idprava',
                                iduzivatele = '$iduzivatele'";
            //echo "<br>$sql<br>";
            if($this->pdo->exec($sqlInsert) === false){
                $jak &= false;
            }else{
                $jak &= true;
            }
        }

        return $jak;
    }

    public function getIduzivateleByLogin(string $login){
        $sql = "SELECT iduzivatele FROM ".TABLE_UZIVATELE." WHERE login = '$login'";
        //echo $sql;
        return $this->pdo->query($sql)->fetchAll()[0]['iduzivatele'];
    }

    public function updateInfoUzivatele(int $iduzivatele, string $jmeno, string $email, string $login)
    {
        $sql = "UPDATE ".TABLE_UZIVATELE." 
                SET jmeno = '$jmeno', 
                    email = '$email',
                    login = '$login'
                WHERE iduzivatele = '$iduzivatele'";

        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }
    }

    public function updateHeslo(int $iduzivatele, string $heslo_nove){
        $sql = "UPDATE ".TABLE_UZIVATELE." 
                SET heslo = '$heslo_nove'
                WHERE iduzivatele = '$iduzivatele'";
        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }
    }

    public function insertClanek(string $nazev_souboru, string $titulek, string $text, int $autor){
        $sql = "INSERT INTO ".TABLE_CLANKY." (
                                            text,
                                            titulek,
                                            obrazek,
                                            iduzivatele
                                             ) VALUES (
                                             '$text',
                                             '$titulek',
                                             '$nazev_souboru',
                                             '$autor'
                                             );";
        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }
    }

    public function updateClanek(string $nazev_souboru = "", string $titulek = "", string $text = "", int $idclanky, int $schvaleno = -1){
        $sql = "";
        if(!empty($nazev_souboru)){
            $sql .= "UPDATE ".TABLE_CLANKY." SET obrazek='$nazev_souboru' WHERE idclanky = '$idclanky';";
        }
        if(!empty($titulek)){
            $sql .= "UPDATE ".TABLE_CLANKY." SET titulek='$titulek' WHERE idclanky = '$idclanky';";
        }
        if(!empty($text)){
            $sql .= "UPDATE ".TABLE_CLANKY." SET text='$text' WHERE idclanky = '$idclanky';";
        }
        if($schvaleno != -1){
            $sql .= "UPDATE ".TABLE_CLANKY." SET schvaleno='$schvaleno' WHERE idclanky = '$idclanky';";
        }

        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }

    }

    public function muzeUzivatelPridatRecenzi(int $iduzivatele, int $idclanky){
        $sql = "SELECT idhodnoceni FROM ".TABLE_HODNOCENI." WHERE iduzivatele='$iduzivatele' and idclanky='$idclanky'";

        return !empty($this->pdo->query($sql)->fetchAll()[0]['idhodnoceni']);
    }

    public function getUzivateleByPravo(int $idprava){
        $sql = "SELECT ".TABLE_UZIVATELE.".iduzivatele,
                       ".TABLE_UZIVATELE.".jmeno
                FROM ".TABLE_UZIVPRAVA."
                    left join ".TABLE_UZIVATELE."
                        on ".TABLE_UZIVPRAVA.".iduzivatele = ".TABLE_UZIVATELE.".iduzivatele
                WHERE ".TABLE_UZIVPRAVA.".idprava = '$idprava'";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function poslatRecenzetum(int $idclanky, array $recenzenti, int $iduzivatele_predal){
        $sql = "";
        foreach($recenzenti as $iduzivatele){
            $sql .= "INSERT INTO ".TABLE_HODNOCENI." (idclanky, iduzivatele, iduzivatele_predal) VALUES ('$idclanky', '$iduzivatele', '$iduzivatele_predal');";
        }
        //echo $sql;

        if($this->pdo->exec($sql) === false){
            return false;
        }else{
            return true;
        }
    }

}



?>