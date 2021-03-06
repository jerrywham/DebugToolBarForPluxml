<?php
/**
* Cette classe permet de sécuriser le debugage PHP dans vos scripts (locaux
* et distant).
* 
* A l'utilisation il vous suffit de l'inclure dans vos script.
*
* @author Jacksay<studio@jacksay.com>
* @author Cyril MAGUIRE<contact@ecyseo.net>
*/
class Debug {

      private static $DEBUG_FLOW = '';
      private static $DEBUG_OUTPUT = '';
      private static $TRAC_NUM = 0;
      private static $debug_instance;
      private static $debug = false;
      protected $version;
      protected $default_lang=DEFAULT_LANG; # langue par defaut de PluXml
      protected static $aLang=array(); # tableau contenant les clés de traduction de la langue courante de PluXml


      public function __construct($default_lang,$version) {

        $this->default_lang = $default_lang;
        $this->version = $version;
        $this->loadLang(PLX_PLUGINS.'DebugToolBarForPluxml/lang/'.$this->default_lang.'.php');

      }
      /****************************************************************************/
      /** CONFIGURATION **/

      // Vous pouvez ajouter votre ip pour un debuggage distant
      // attention cependant
      public static $allow_IP = array('::1','127.0.0.1');
      
      /****************************************************************************/

      /**
     * Méthode qui charge le fichier de langue par défaut du plugin
     *
     * @param filename  fichier de langue à charger
     * @return  null
     * @author  Stephane F
     **/
    private function loadLang($filename) {
      if(!is_file($filename)) return;
      include($filename);
      self::$aLang=$LANG;
    }

    /**
     * Méthode qui affiche une clé de traduction dans la langue par défaut de PluXml
     *
     * @param key   clé de traduction à récuperer
     * @return  stdio
     * @author  Stephane F
     **/
    public function lang($key='') {
      if(isset(self::$aLang[$key]))
        echo self::$aLang[$key];
      else
        echo $key;
    }

    /**
     * Méthode qui retourne une clé de traduction dans la langue par défaut de PluXml
     *
     * @param key   clé de traduction à récuperer
     * @return  string  clé de traduite
     * @author  Stephane F
     **/
    public static function getLang($key='') {
      if(isset(self::$aLang[$key]))
        return self::$aLang[$key];
      else
        return $key;
    }

    /**
    * Equivalent à un var_dump mais en version sécurisée et en couleur.
    *
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 1.0
    */
    private static function _trac( $mixedvar, $comment='',  $sub = 0, $index = false ){
      $type = htmlentities(gettype($mixedvar));

      $r ='';
      switch ($type) {
        case 'NULL':$r .= '<em style="color: #0000a0; font-weight: bold;">NULL</em>';break;
        case 'boolean':if($mixedvar) $r .= '<span style="color: #327333; font-weight: bold;">TRUE</span>';
        else $r .= '<span style="color: #327333; font-weight: bold;">FALSE</span>';break;
        case 'integer':$r .= '<span style="color: red; font-weight: bold;">'.$mixedvar.'</span>';break;
        case 'double':$r .= '<span style="color: #e8008d; font-weight: bold;">'.$mixedvar.'</span>';break;
        case 'string':$r .= '<span style="color: '.($index === true ? '#e84a00':'#000').';">\''.$mixedvar.'\'</span>';break;
        case 'array':$r .= self::getLang('L_ARRAY').'('.count($mixedvar).') &nbsp;{'."\r\n\n";
        foreach($mixedvar AS $k => $e) $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'['.self::_trac($k, $comment, $sub+1, true).'] =&gt; '.($k === 'GLOBALS' ? '* RECURSION *':self::_trac($e, $comment, $sub+1)).",\r\n";
            $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub).'}';
            break;
        case 'object':$r .= self::getLang('L_OBJECT').' «<strong>'.htmlentities(get_class($mixedvar)).'</strong>»&nbsp;{'."\r\n\n";
          $prop = get_object_vars($mixedvar);
          foreach($prop AS $name => $val){
            if($name == 'privates_variables'){
              for($i = 0, $count = count($mixedvar->privates_variables); $i < $count; $i++) $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'<strong>'.htmlentities($get = $mixedvar->privates_variables[$i]).'</strong> =&gt; '.self::_trac($mixedvar->$get, $comment, $sub+1)."\r\n\n";
              continue;
            }
            $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub+1).'<strong>'.htmlentities($name).'</strong> =&gt; '.self::_trac($val, $comment, $sub+1)."\r\n\n";
          }
          $r .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sub).'}';break;
        default:$r .= self::getLang('L_TYPE').' <strong>'.$type.'</strong>.';break;
      }
      $r = preg_replace('/\[(.*)\]/', '[<span class="jcktraker-id">$1</span>]', $r);
      return $r;
    }
    /**
    * Pour tracer une variable
    *
    * @author  Jacksay<studio@jacksay.com>
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 2.0
    */
    public static function trac( $mixedvar, $comment='',  $sub = 0 ) {
      $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $printDebug = '';
      foreach ($debug as $key => $value) {
        if ($value['function'] == 'trac') {
          $function = 'trac';
          $line = $value['line'];
          $file = $value['file'];
        }
        if ($line == '') {
          if ($value['function'] == 'd') {
            $function = 'd';
            $line = $value['line'];
            $file = $value['file'];
            break;
          }
        }
      }
      $printDebug .=  '<p class="jcktraker-backtrace">'."\n".'&nbsp;'.self::getLang('L_CALL').' <strong>'.$function.'()</strong> '.self::getLang('L_LINE').' '.$line. ' '.self::getLang('L_OF_FILE')."\n\n".'&nbsp;<strong><em>'.$file.'</em></strong>'."\n\n".'<br/></p><br/>';
      
      $FILE = fopen( $file, 'r' );
      $LINE = 0;
      if ($comment == '') {
          while ( ( $row = fgets( $FILE ) ) !== false ) {
            if ( ++$LINE == $line ) {
                $row = str_replace(array('<','>'), '', $row);
                preg_match('/(?:.*)*d\((.*)\);(?:.*)*/U', $row, $match);
                if (isset($match[1])) $comment = $match[1];
                preg_match('/(?:.*)*Debug::trac\((.*)\);(?:.*)*/U',$row, $match);
                if (isset($match[1])) $comment = $match[1];
              break;
            }
          }
          fclose( $FILE );
      }
      $r = self::_trac( $mixedvar, $comment, $sub);
      $r .= "\n\n\n"; 
      self::$DEBUG_OUTPUT .= '<pre id="jcktraker-backtrace-'.self::$TRAC_NUM.'">'."\n\n".$printDebug.'<strong class="jcktraker-blue">'.$comment.'</strong> = '. $r ."</pre>\n";
      self::$TRAC_NUM++;
    }
    /**
    * Pour décomposer une variable globale
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 1.0
    */
    private static function _color($value) {
      return "\n\n".self::_trac($value)."\n\n\n";
    }

    /**
    * Affiche une petite ligne pour suivre le fil de l'exécution.
    * A utiliser dans un foreach par exemple pour savoir quel valeur prend une variable
    *
    * @author  Jacksay<studio@jacksay.com>
    * @version 1.0
    */
    public static function flow( $message, $type=1 ) {
      $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $printDebug = '';
      foreach ($debug as $key => $value) {
        if ($value['function'] == 'flow') {
          $function = 'flow';
          $line = $value['line'];
          $file = $value['file'];
        }
        if ($line == '') {
          if ($value['function'] == 'f') {
            $function = 'f';
            $line = $value['line'];
            $file = $value['file'];

          }
        }
      }
      $printDebug .=  '<p class="jcktraker-backtrace">'."\n".'&nbsp;'.self::getLang('L_CALL').' <strong>'.$function.'()</strong> '.self::getLang('L_LINE').' '.$line. ' '.self::getLang('L_OF_FILE')."\n\n".'&nbsp;<strong><em>'.$file.'</em></strong>'."\n\n".'<br/></p><br/>';
      

      $FILE = fopen( $file, 'r' );
      $LINE = 0;
      $comment == '';
          while ( ( $row = fgets( $FILE ) ) !== false ) {
            if ( ++$LINE == $line ) {
                $row = str_replace(array('<','>'), '', $row);
                preg_match('/(?:.*)*f\((.*)\);(?:.*)*/U', $row, $match);
                if (isset($match[1])) $comment = $match[1];
                preg_match('/(?:.*)*Debug::flow\((.*)\);(?:.*)*/U',$row, $match);
                if (isset($match[1])) $comment = $match[1];
              break;
            }
          }
          fclose( $FILE );
     if ( self::$DEBUG_FLOW!=$printDebug ) {
        self::$DEBUG_FLOW = $printDebug;
        self::$DEBUG_OUTPUT .= self::$DEBUG_FLOW.'<p class="jcktraker-flow-'.$type.'">'.$comment.' = '.htmlentities($message)."</p>\n";     
     } else {
        self::$DEBUG_OUTPUT .= '<p class="jcktraker-flow-'.$type.'">'.$comment.' = '.htmlentities($message)."</p>\n";
     }
      self::$TRAC_NUM++;
    }

    /**
    * Cette méthode est automatiquement appelée lorsque vous importez le fichier
    * JckTraker.php dans votre script.
    *
    * @author  Jacksay<studio@jacksay.com>
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 2.0
    */
    public static function init() {
      if(in_array($_SERVER['REMOTE_ADDR'], self::$allow_IP)){
        self::$debug = true;
        //error_reporting(E_ALL);
      } else {
        self::$debug = false;
        error_reporting(0);
      }
    }


    /**
    * Accesseur
    *
    * @author  Jacksay<studio@jacksay.com>
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 2.0
    */
    public static function getDebugInstance($default_lang,$version) {
      if(!isset (self::$debug_instance) ){
        self::$debug_instance = new Debug($default_lang,$version);self::$debug = true;
        self::init();
      }
      return self::$debug_instance;
    }

    /**
    * Elément clef, va afficher la barre de debug dans votre page.
    * A placer juste avant la balise </body>
    *
    * @author  Jacksay<studio@jacksay.com>
    * @author  Cyril MAGUIRE<contact@ecyseo.net>
    * @version 2.0
    */
    public function printBar() {
      if( !self::$debug ) return;
      ?>
      <!-- JCK TRAKER BOX v1.0 -->
      <script type="text/javascript">
      function jcktraker_hide(){
        var sections = document.getElementsByName('jcktraker-section');
        var num_sections = sections.length;
        for( var i=0; i<num_sections; i++ ){
          sections[i].style.display = 'none';    
        }
      }
      function jcktraker_toogle( section, dispatcher ){
        var section_blk = document.getElementById(section);
        if( section_blk.style.display != 'block'){
          jcktraker_hide();
          section_blk.style.display = 'block';
          dispatcher.style.fontWeight = 'bold';
          dispatcher.style.backgroundColor = '#990000';
          dispatcher.style.color = '#FFFFFF';
        }
        else {
          section_blk.style.display = 'none';
          dispatcher.style.fontWeight = "normal";
          dispatcher.style.backgroundColor = '#000000';
          dispatcher.style.color = '#FFFFFF';
        }
      }
      </script>
      <style type="text/css">
      .jcktraker-blue {
        color:#8bb5eb;
      }
      .jcktraker-id {
        color:#e8008d;
      }
      #jcktraker-box {
        z-index:99999;
        position: fixed;
        bottom: 0;
        right: 0;
        font-size: 10px;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        max-height: 100%;
        max-width: 75%;
        margin: 0;
        padding: 0;
        -moz-border-radius: .4em;
        -moz-box-shadow: 0 0 5em #000;
        border-radius: .4em;
        box-shadow: 0 0 5em #000;
      }
      #jcktraker-box *{
        margin: 0;
        padding: 0;
        border-radius: .4em;
        -moz-border-radius: .4em;
      }
      #jcktraker-box pre{
        color:#000;
        margin: 0 2em;
        border: dotted thin #999;
        border-radius: .4em;
        box-shadow: 0 0 1em #000 inset;
        -moz-border-radius: .4em;
        -moz-box-shadow: 0 0 5em #000 inset;
        padding: .4em .6em;
        background-color: #e4e4e4;
        font-size: 1.2em;
        white-space: pre;           /* CSS 2.0 */
        white-space: pre-wrap;      /* CSS 2.1 */
        white-space: pre-line;      /* CSS 3.0 */
        white-space: -pre-wrap;     /* Opera 4-6 */
        white-space: -o-pre-wrap;   /* Opera 7 */
        white-space: -moz-pre-wrap; /* Mozilla */
        white-space: -hp-pre-wrap;  /* HP Printers */
        word-wrap: break-word;      /* IE 5+ */
      }
      #jcktraker-box p{
        margin: 0 1em;
      }
      ul#jcktraker-menu li {
        display: inline;
        padding: 0 .4em;
        line-height: 2em;
      }
      ul#jcktraker-menu li[onclick]:hover {
        background: #990000;cursor: pointer;
      }

      #jcktraker-box div[name="jcktraker-section"] {
        display: none;
        white-space: pre-wrap;
        overflow: hidden;
        max-width: 100%;
        max-height: 580px;
        background: #111;
        color: #fff;
        opacity: .7;
      }
      #jcktraker-box div[name="jcktraker-section"]:hover {
        opacity: 1;
      }
      #jcktraker-box div[name="jcktraker-section"] pre {
        height: 460px;
        overflow: scroll;
      }
      #jcktraker-own {
        padding-bottom: 30px;
      }
      #jcktraker-menu {
        background: #000;
        color: #fff;
        white-space:nowrap;
        text-align: right;
        -moz-border-radius: .4em 0 0 0;
        border-radius: .4em 0 0 0;
      }
      .jcktraker-backtrace {
        background-color: #e4a504;
      }
      .jcktraker-backtrace-close {
        display: block;
        position: relative;
        background-color: red;
        padding:5px;
        float:right;
        cursor: pointer;
      }
      #jcktraker-pre {
        height: 530px;
        overflow: scroll;
      }

      </style>
      <div id="jcktraker-box">
        <div id="jcktraker-post" name="jcktraker-section">
          <strong>$_POST</strong>
          <pre><?php echo self::_color($_POST); ?></pre>
        </div>
        <div id="jcktraker-files" name="jcktraker-section">
          <strong>$_FILES</strong>
          <pre><?php echo self::_color($_FILES); ?></pre>
        </div>
        <div id="jcktraker-get" name="jcktraker-section">
          <strong>$_GET</strong>
          <pre><?php echo self::_color($_GET); ?></pre>
        </div>
        <div id="jcktraker-server" name="jcktraker-section">
          <strong>$_SERVER</strong>
          <pre><?php echo self::_color($_SERVER); ?></pre>
        </div>
        <div id="jcktraker-session" name="jcktraker-section">
          <strong>$_SESSION</strong>
          <pre><?php if(isset($_SESSION)) echo self::_color($_SESSION); ?></pre>
        </div>
        <div id="jcktraker-cookie" name="jcktraker-section">
          <strong>$_COOKIE</strong>
          <pre><?php echo self::_color($_COOKIE); ?></pre>
        </div>
        <div id="jcktraker-request" name="jcktraker-section">
          <strong>$_REQUEST</strong>
          <pre><?php echo self::_color($_REQUEST); ?></pre>
        </div>
        <div id="jcktraker-own" name="jcktraker-section">
          <strong><?php echo (self::$DEBUG_FLOW == '') ? self::getLang('L_YOUR_TRAC') : self::getLang('L_YOUR_FLOW') ?></strong>
          <div id="jcktraker-pre">
            <?php echo self::$DEBUG_OUTPUT; ?>

          </div>
        </div>
        <ul id="jcktraker-menu">
          <li><strong>ToolBarDebug <span>v <?php echo $this->version ?> </span></strong></li>
          <li id="jacktraker_own_button" onclick="jcktraker_toogle('jcktraker-own', this)"><?php echo (self::$DEBUG_FLOW == '') ? self::getLang('L_TRAC') : self::getLang('L_FLOW') ?>(<?php echo self::$TRAC_NUM ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-post', this)">$_POST(<?php echo count($_POST) ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-files', this)">$_FILES(<?php echo count($_FILES) ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-get', this)">$_GET(<?php echo count($_GET) ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-server', this)">$_SERVER(<?php echo count($_SERVER) ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-session', this)"><?php if(isset ($_SESSION)) { echo '$_SESSION(',count($_SESSION),')'; } else { echo '<del>$_SESSION</del>';} ?></li>
          <li onclick="jcktraker_toogle('jcktraker-cookie', this)">$_COOKIE(<?php echo count($_COOKIE) ?>)</li>
          <li onclick="jcktraker_toogle('jcktraker-request', this)">$_REQUEST(<?php echo count($_REQUEST) ?>)</li>
        </ul>
      </div>
      <?php if(!empty (self::$DEBUG_OUTPUT) ): ?>
      <script type="text/javascript">jcktraker_toogle('jcktraker-own', document.getElementById('jacktraker_own_button'));</script>
    <?php endif;

    }
}
//if (DEBUG == 1) {
/**
* Dump variable
* Alias of Debug::trac()
*/
if ( !function_exists( 'd' ) ) {
  function d() {
    call_user_func_array( array( 'Debug', 'trac' ), func_get_args() );
  }
}
/**
* Dump variable
* Alias of Debug::flow()
*/
if ( !function_exists( 'f' ) ) {
  function f() {
    call_user_func_array( array( 'Debug', 'flow' ), func_get_args() );
  }
}
//}
?>
