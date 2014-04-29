<?php


//oggetto anonimo
class AnObj extends stdClass
{
	
	public function __call($closure, $args)
    {
		return call_user_func_array($this->{$closure}->bindTo($this),$args);
    }
	
	public function __toString()
	{
		return call_user_func($this->{"__toString"}->bindTo($this));
	}
	
}


//ORM generico
class ORM extends AnObj
{
	public $pdo;
	
	function __toString()
	{
		return "ORM: null";
	}
	
	//oggetto anonimo della tabella
	public function __table($name)
	{
		$o=new AnObj();
		$o->orm=$this;
		$o->name=$name;
	
		$o->columns=function()
		{
			return $this->orm->columns($this->name);
		};
		
		$o->column=function($name)
		{
			return $this->columns()[$name];
		};

		$o->pks=function()
		{
			return $this->orm->pks($this->name);
		};
		
		$o->pk=function($name)
		{
			return $this->pks($this->name)[$name];
		};
		
		$o->__toString=function()
		{
			return "table:".$this->name;
		};
		return $o;
	}
	
	//oggetto anonimo della colonna
	public function __column($name,$type,$table)
	{
		$o=new AnObj();
		$o->orm=$this;
		$o->name=$name;
		$o->type=$type;
		$o->table=$table;
		
		$o->__toString=function()
		{
			return "column:".$this->name;
		};
		
		$o->fk=function()
		{
			return $this->orm->fk($this->table->name,$this->name);
		};		
		return $o;
	}
	
	//oggetto anonimo del record
	public function __record($name,$type,$table)
	{
		$o=new AnObj();
		$o->orm=$this;
		$o->table=$table;
		
		$o->__toString=function()
		{
			return "record:";
		};
		
		return $o;
	}
	
	public function tables()
	{
		return array();
	}
	
	public function table($name)
	{
		return $this->tables()[$name];
	}
	
	public function columns($tablename)
	{
		return array();
	}
	
	public function column($tablename,$columnname)
	{
		return $this->columns($tablename)[$columnname];
	}
	
	public function fks($tablename)
	{
		return array();
	}
	
	public function fk($tablename,$columnname)
	{
		return $this->fks($tablename)[$columnname];
	}
	
	public function pks($tablename)
	{
		return array();
	}
	
	public function pk($tablename,$columnname)
	{
		return $this->pks($tablename)[$columnname];
	}
};

class DBObj
{
	public function __construct($orm,$table)
	{
		
	}
}



//ORM specifico SQLITE
class ORMsqlite extends ORM
{
	public function __construct($db)
	{
		$this->pdo=new PDO('sqlite:'.$db);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	}
	
	function __toString()
	{
		return "ORM: SQLite";
	}

	public function tables()
	{
		$r = array();
		foreach ($this->pdo->query("SELECT * FROM sqlite_master WHERE type='table'") as $t)
			$r[$t["tbl_name"]]=$this->__table($t["tbl_name"]);
		return $r;
	}
	
	public function columns($tablename)
	{
		$r = array();
		$table=$this->table($tablename);
		$sth=$this->pdo->prepare("PRAGMA table_info('".$table->name."')");
		$sth->execute();
		foreach ($sth->fetchAll() as $t)
			$r[$t["name"]]=$this->__column($t["name"],$t["type"],$table);
		return $r;
	}
	
	public function fks($tablename)
	{
		$r = array();
		$table=$this->table($tablename);
		$sth=$this->pdo->prepare("PRAGMA foreign_key_list('".$table->name."')");
		$sth->execute();
		foreach ($sth->fetchAll() as $t)
			$r[$t["to"]]=$this->table($t["table"])->column($t["to"]);
		return $r;
	}
	
	public function pks($tablename)
	{
		$r = array();
		$table=$this->table($tablename);
		$sth=$this->pdo->prepare("PRAGMA table_info('".$table->name."')");
		$sth->execute();
		foreach ($sth->fetchAll() as $t)
			if ($t["pk"])
				$r[$t["name"]]=$this->__column($t["name"],$t["type"],$table);
		return $r;
	}
	
};



$orm=new ORMsqlite("Chinook.sqlite");
//print_r($orm->table("Album")->columns());
//print_r($orm->fks("Album"));
//print_r($orm->table("Album")->column("ArtistId")->fk());



//echo $orm;
//echo $orm->table("Album")
echo $orm->table("Album")->pk("AlbumId");








?>
