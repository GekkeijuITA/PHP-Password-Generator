<?php
    function randomChars($length , $password)
    {
        for($i = 0 ; $i < $length ; $i++)
        {
            $rand = rand(40,122);
            while($rand == 60 || $rand == 62)
            {
                $rand = rand(40,122);
            }
            $password .= chr($rand);
        }
        return $password;
    }

    function encrypt($password , $code)
    {
        $passwordHash = "";
        for($i = 0 ; $i < strlen($password) ; $i++)
        {
            $char = ord($password[$i]);
            $newChar = $char + $code;
            if($newChar > 122)
            {
                $diff = $newChar - 122;
                $newChar = 40 + $diff;
            }
            $passwordHash .= chr($newChar); 
        }
        return $passwordHash;
    }

    $website = "";
    $email = "";
    $password = "";
    $fileXml = "passwordStorage.xml";
    $path = 'data/'.$fileXml;

    if(isset($_POST["generate"]))
    {
        $max = 16; //Final len of passwords
        $website = $_POST["website"];
        $email = $_POST["email"];
        $password = "";
        $length = strlen($password);
        while($length < $max)
        {
            $password = randomChars(($max-$length) , $password);
            $length = strlen($password);
        }
        $fileCodes = file_get_contents("data/code.txt");
        $codes = explode(" " , $fileCodes);
        $passwordToSave = encrypt(encrypt(encrypt($password , $codes[0]) , $codes[1]) , $codes[2]);
        
        //File xml
        if(file_exists($path))
        {
            $fileXml = simplexml_load_file($path);
            foreach($fileXml -> children() as $row)
            {
                $idValue = $row["id"];
            }
            $idValue += 1;
            $fileXml = new DomDocument('1.0' , 'utf-8');                
            $fileXml -> formatOutput=true;
            $fileXml -> preserveWhiteSpace=false;
            $fileXml -> load($path);
        }
        else
        {
            $fileXml = new DomDocument('1.0' , 'utf-8');                
            $fileXml -> formatOutput=true;
            $fileXml -> preserveWhiteSpace=false;
            $root = $fileXml -> createElement("document");
            $fileXml -> appendChild($root);
            $idValue = 1;
        }

        //Add
        $site = $fileXml -> createElement("site");
        $name = $fileXml -> createAttribute("name");
        $name -> value = $website;
        $id = $fileXml -> createAttribute("id");
        $id -> value = $idValue;
        $username = $fileXml -> createElement("username" , $email);
        $psw = $fileXml -> createElement("password" , $passwordToSave);

        $fileXml -> documentElement -> appendChild($site);
        $site -> appendChild($name);
        $site -> appendChild($id);
        $site -> appendChild($username);
        $site -> appendChild($psw);

        $fileXml -> save($path);
    }

    if(isset($_POST["delete"]))
    {
        $id = $_POST["id"];
        $fileXml = new DomDocument('1.0' , 'utf-8');                
        $fileXml -> formatOutput=true;
        $fileXml -> preserveWhiteSpace=false;
        $fileXml -> load($path);

        $root = $fileXml -> documentElement;
        $list = $root -> getElementsByTagName('site');
        $nodeToRemove = null;
        foreach($list as $domElement)
        {
            $attrValue = $domElement -> getAttribute('id');
            if($attrValue == $id)
            {
                $nodeToRemove = $domElement;
                break;
            }
        }
        if($nodeToRemove != null)
        {
            $root -> removeChild($nodeToRemove);
        }
        $fileXml -> save($path);
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Password Manager</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">  
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/bootstrap.min.css"> 
    </head>
    <body>
        <div id="Generator">
            <h1>Generator</h1>
            <form method="POST" action="">
                <input type="text" name="website" placeholder="Website" value="<?php echo $website?>" required>
                <input type="email" name="email" placeholder="Email" value="<?php echo $email?>" required>
                <input type="text" name="password" placeholder="Password generated" value="<?php echo $password?>" readonly>
                <input type="submit" name="generate" value="Generate">
            </form>
        </div>
        <div id="Manager">
            <h1>Manager</h1>
            <form method="POST" action="">
                <input type="text" name="website" placeholder="Website">
                <input type="submit" name="search" value="Search">
                <input type="submit" name="displayAll" value="Show All">
            </form>
            <?php
                function decrypt($password , $code)
                {
                    $passwordDecrypted = "";
                    $len = strlen($password);
                    $password = str_split($password);
                    for($i = 0 ; $i < $len ; $i++)
                    {
                        $char = ord($password[$i]);
                        $newChar = $char - $code;
                        if($newChar < 40)
                        {
                            $diff = 40 - $newChar;
                            $newChar = 122 - $diff;
                        }
                        $passwordDecrypted .= chr($newChar);
                    }
                    return $passwordDecrypted;
                }

                if(isset($_POST["displayAll"]))
                {
                    if(file_exists($path))
                    {
                        $count = 0;
                        $fileCodes = file_get_contents("data/code.txt");
                        $codes = explode(" " , $fileCodes);
                        $fileXml = simplexml_load_file($path);
                        foreach($fileXml -> children() as $row)
                        {
                            $count++;
                            $echoWebsite = $row["name"];
                            $echoUsername = $row -> username;
                            $id = $row["id"];
                            $echoPassword = decrypt(decrypt(decrypt($row -> password , $codes[2]) , $codes[1]) , $codes[0]);
                            echo '
                                <div>
                                    <h4>'.$echoWebsite.'</h4>
                                    <p>'.$echoUsername.'</p>
                                    <p>'.$echoPassword.'</p>
                                    <button data-bs-toggle="modal" data-bs-target="#areusure" data-bs-whatever="'.$id.'">Delete</button>
                                </div>
                            ';
                        }
                        if($count == 0)
                        {
                            echo "You haven't registered your passwords yet";
                        }
                    }  
                    else
                    {
                        echo "You haven't registered your passwords yet";
                    }   
                }

                if(isset($_POST["search"]))
                {
                    if(!empty($_POST["website"]))
                    {
                        if(file_exists($path))
                        {
                            $count = 0;
                            $fileCodes = file_get_contents("data/code.txt");
                            $codes = explode(" " , $fileCodes);
                            $fileXml = simplexml_load_file($path);
                            foreach($fileXml -> children() as $row)
                            {
                                $echoWebsite = $row["name"];
                                if($echoWebsite == $_POST["website"])
                                {
                                    $id = $row["id"];
                                    $count++;
                                    $echoUsername = $row -> username;
                                    $echoPassword = decrypt(decrypt(decrypt($row -> password , $codes[2]) , $codes[1]) , $codes[0]);
                                    echo '
                                        <div>
                                            <h4>'.$echoWebsite.'</h4>
                                            <p>'.$echoUsername.'</p>
                                            <p>'.$echoPassword.'</p>
                                        </div>
                                        <button data-bs-toggle="modal" data-bs-target="#areusure" data-bs-whatever="'.$id.'">Delete</button>
                                    ';
                                }
                            }
                            if($count == 0)
                            {
                                echo "You haven't registered your passwords in this website yet";
                            }
                        }  
                        else
                        {
                            echo "You haven't registered your passwords yet";
                        } 
                    }
                    else
                    {
                        echo "Please insert the website in the field";
                    }
                }                    
            ?>
        </div>

        <!--Modal-->
        <div class="modal fade" id="areusure" tabindex="-1" aria-labelledby="areusureLabel" aria-hidden="true" style="color:black;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="areusureLabel">Attenzione!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure about wanting to delete this password?
                        <form method="post" action="">
                            <input type="hidden" name="id" value="">
                    </div>
                    <div class="modal-footer">
                            <input type="submit" name="delete" class="btn btn-danger" value="Elimina">
                        </form>                               
                    </div>
                </div>
            </div>
        </div>
         
    </body>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
        }    
    </script>  
    <script>
        var modal = document.getElementById("areusure");
        modal.addEventListener('show.bs.modal' , function(event)
        {
            var button = event.relatedTarget;
            var recipient = button.getAttribute('data-bs-whatever');
            var modalBodyInput = modal.querySelector('.modal-body input');
            modalBodyInput.value = recipient;
        })
    </script>
</html>