<?php

abstract class DataBoundObject { //creamos clase abstracta databoundobject

   protected $ID; //ponemos atributos protegidos
   protected $objPDO;
   protected $strTableName;
   protected $arRelationMap;
   protected $blForDeletion;
   protected $blIsLoaded;
   protected $arModifiedRelations;

   abstract protected function DefineTableName(); //creamos una función abstracta protegida
   abstract protected function DefineRelationMap(); //creamos una función abstracta protegida

   public function __construct(PDO $objPDO, $id = NULL) { //creamos una función pública construct que contiene pdo y la ID;
      $this->strTableName = $this->DefineTableName(); //igualamos atributo strTable con la función definetable
      $this->arRelationMap = $this->DefineRelationMap(); //lo mismo pero con edfinerelationmap 
      $this->objPDO = $objPDO; //indicamos que el atributo objpdo es objPdo
      $this->blIsLoaded = false; //declaramos falso blIsLoaded
      if (isset($id)) { //si está id
         $this->ID = $id; //guardamos $id en id
      };
      $this->arModifiedRelations = array(); //igualamos arModifiedRelations en un array
   }

   public function Load() { //creamos función pública load
      if (isset($this->ID)) { //si está id
		$strQuery = "SELECT "; //hacemos un select y lo guardamos en strQuery
        foreach ($this->arRelationMap as $key => $value) { //bucle flor 
			$strQuery .= "\"" . $key . "\","; //separamos con ""
        }
        $strQuery = substr($strQuery, 0, strlen($strQuery)-1); //igualamos strQuery con el resultado de la posición 0
        $strQuery .= " FROM " . $this->strTableName . " WHERE \"id\" = :eid"; //hacemos el select
        $objStatement = $this->objPDO->prepare($strQuery);
        $objStatement->bindParam(':eid', $this->ID, PDO::PARAM_INT);
        $objStatement->execute();
        $arRow = $objStatement->fetch(PDO::FETCH_ASSOC);
        foreach($arRow as $key => $value) {
            $strMember = $this->arRelationMap[$key];
            if (property_exists($this, $strMember)) {
                if (is_numeric($value)) {
                   eval('$this->'.$strMember.' = '.$value.';');
                } else {
                   eval('$this->'.$strMember.' = "'.$value.'";');
                };
            };
         };
         $this->blIsLoaded = true;
      };
   }

   public function Save() { //creamos función save
      if (isset($this->ID)) { //A partir de aquí es lo mismo, pero en vez de un select es un update
         $strQuery = 'UPDATE "' . $this->strTableName . '" SET ';
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (array_key_exists($value, $this->arModifiedRelations)) {
               $strQuery .= '"' . $key . "\" = :$value, ";
            };
         }
         $strQuery = substr($strQuery, 0, strlen($strQuery)-2);
         $strQuery .= ' WHERE "id" = :eid';
         unset($objStatement);
         $objStatement = $this->objPDO->prepare($strQuery);
         $objStatement->bindValue(':eid', $this->ID, PDO::PARAM_INT);
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (array_key_exists($value, $this->arModifiedRelations)) {
               if ((is_int($actualVal)) || ($actualVal == NULL)) {
                  $objStatement->bindValue(':' . $value, $actualVal,PDO::PARAM_INT);
               } else {
                  $objStatement->bindValue(':' . $value, $actualVal,PDO::PARAM_STR);
               };
            };
         };
         $objStatement->execute();
      } else { //si no
         $strValueList = ""; //campo vacío
         $strQuery = 'INSERT INTO "' . $this->strTableName . '"('; //hacemos insert
         foreach ($this->arRelationMap as $key => $value) { //bucle flor
            eval('$actualVal = &$this->' . $value . ';');
            if (isset($actualVal)) { //si está
               if (array_key_exists($value, $this->arModifiedRelations)) {
                  $strQuery .= '"' . $key . '", ';
                  $strValueList .= ":$value, ";
               };
            };
         }
         $strQuery = substr($strQuery, 0, strlen($strQuery) - 2);
         $strValueList = substr($strValueList, 0, strlen($strValueList) - 2);
         $strQuery .= ") VALUES (";
         $strQuery .= $strValueList;
         $strQuery .= ")";

         unset($objStatement);
         $objStatement = $this->objPDO->prepare($strQuery);
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (isset($actualVal)) {   
               if (array_key_exists($value, $this->arModifiedRelations)) {
                  if ((is_int($actualVal)) || ($actualVal == NULL)) {
                     $objStatement->bindValue(':' . $value, $actualVal, PDO::PARAM_INT);
                  } else {
                     $objStatement->bindValue(':' . $value, $actualVal, PDO::PARAM_STR);
                  };
               };
            };
         }
         $objStatement->execute();
         $this->ID = $this->objPDO->lastInsertId($this->strTableName . "_id_seq");
      }
   }

   public function MarkForDeletion() { //creamos función markfordeletion
      $this->blForDeletion = true; //ponemos true en blForDeletion
   }
   
   public function __destruct() { //función para borrar
      if (isset($this->ID)) {    //si existe id
         if ($this->blForDeletion == true) { //igualamos a true
            $strQuery = 'DELETE FROM "' . $this->strTableName . '" WHERE "id" = :eid'; //borramos los campos
            $objStatement = $this->objPDO->prepare($strQuery);
            $objStatement->bindValue(':eid', $this->ID, PDO::PARAM_INT);   
            $objStatement->execute(); //ejecutamos
         };
      }
   }

   public function __call($strFunction, $arArguments) { //función que llama y añade $strfunction y $arArguments

      $strMethodType = substr($strFunction, 0, 3);
      $strMethodMember = substr($strFunction, 3);
      switch ($strMethodType) { //hacemos u ncaso de switch
         case "set":
            return($this->SetAccessor($strMethodMember, $arArguments[0])); //caso set devolvemos valor
            break;
         case "get":
            return($this->GetAccessor($strMethodMember));    //caso get devolvemos valor
      };
      return(false);   //devolvemos falso
   }

   private function SetAccessor($strMember, $strNewValue) { //creamos función setAccesor privada con 2 atributos
      if (property_exists($this, $strMember)) { //si existe la propiedad
         if (is_numeric($strNewValue)) { //si es numérico
            eval('$this->' . $strMember . ' = ' . $strNewValue . ';');
         } else {
            eval('$this->' . $strMember . ' = "' . $strNewValue . '";'); //ponemos comillas
         };
         $this->arModifiedRelations[$strMember] = "1"; //damos valor de 1
         return($this);
      } else {
         return(false); //si no, devolvemos falso
      };   
   }

   private function GetAccessor($strMember) { //creamos la función getAccesor con valor strmember
      if ($this->blIsLoaded != true) { //si es diferente de true
         $this->Load(); //cargamos la función load
      }
      if (property_exists($this, $strMember)) { //si la propiedad existe
         eval('$strRetVal = $this->' . $strMember . ';');
         return($strRetVal); //devolvemos strRetVal
      } else {
         return(false); //si no devolvemos falso
      };   
   }
   
}

?>
