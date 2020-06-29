<?php
error_reporting(E_ALL ^E_NOTICE ^E_WARNING ^E_STRICT);
date_default_timezone_set('America/Bogota');
set_time_limit(60);
session_start();


$config['refresh'] = 5000;
$config['pause'] = 30000;

if (isset($_GET['r'])) $config['refresh'] = $_GET['r'] * 1000;
if (isset($_GET['p'])) $config['pause'] = $_GET['p'] * 1000;

//Agregamos el archivo autoload
$config['autoload'] = 'php-graph-sdk-5.x/src/Facebook/autoload.php';
if (file_exists($config['autoload'])){
	require_once($config['autoload']);
}else{
	exit('Por favor instale el PHP SDK de Facebook <br><a href="https://github.com/facebookarchive/php-graph-sdk/tree/5.x" target="_blank" >https://github.com/facebookarchive/php-graph-sdk/tree/5.x</a>');
}

define('conf_appid','307829177273358');
define('conf_appsecret','4cc92c26ad2ff20d9c4c12247e331a67');

define('conf_fanpage','458131934379710');
define('conf_fanpagetoken','EAAEXZBBDDTA4BABjZC1to51F33cQzmZB0o5uIUfkZCOsr6xWnt1KOAwuWg2I5ftMDAsGtszm5ny7FIiQK2uvJrLxbrbZACYDTGTf5rzlv3lWPb0BYZCRii6VNbB2pvfwC4m14n48FjXZB5GKqIWfG94YhXMCHXZCslOwEanaKRtumwZDZD');

//Inicializamos la clase.
$fb = new Facebook\Facebook([
  'app_id' => conf_appid, 
  'app_secret' => conf_appsecret,
  'default_access_token' => conf_fanpagetoken, 
  'default_graph_version' => 'v7.0'  //Ultima Version del Graph 
]);
 
try {
	$fbget = $fb->get( '/'.conf_fanpage.'/?fields=live_videos.limit(1){title,status,stream_url,secure_stream_url,embed_html,id,description,video,comments.order(reverse_chronological){from{picture,name},created_time,message,attachment}}');

	$fbdata = $fbget->getDecodedBody();//Convierte a array los datos que solicitamos


	$comentarios = $fbdata['live_videos']['data'][0]['comments']['data'];

	foreach($comentarios as $row => $value){
		$foto = $value['from']['picture'];
		$hora = date('Y-m-d H:i:s', strtotime($value['created_time']));
		if ($value['message'] == '') $value['message'] = '<img class="fb-sticker" src="'.$value['attachment']['url'].'" />';
		$fbvalor[] = array(
			'id'=>$value['id'],
			'time'=>  $hora,
			'name'=>$value['from']['name'],
			'message'=>$value['message'],
			'emoticon'=>$value['attachment']['url'],
			'picture'=>$foto['data']['url']
		);
	}
	sort($fbvalor);
	if(isset($_GET['format'])){
		if($_GET['format']=='json'){
			header('Content-Type: application/json');
			echo json_encode($fbvalor, JSON_PRETTY_PRINT);
			exit();
		}
	}
} catch(\Facebook\Exception\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(\Facebook\Exception\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

?>
<html>
<head>
<meta charset="utf-8" />
<title>Facebook Commentarios</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
<meta property="fb:app_id" content="<?php echo conf_appid; ?>">
<script src="https://code.jquery.com/jquery-3.5.0.js"></script>
<style>
*{
	box-sizing: border-box;
}
body {
	background-color: rgba(0, 0, 0, 0); margin: 0px auto; color: #1d2129;
    /*text-shadow: 2px 2px 2px #000;*/
	font-family: Helvetica, Arial, sans-serif;
    font-size: 1.1em;	
	    padding: 5px;
	}

.comment{
    display:flex;
	max-width: 500px;
}
.comment p{
    width: 100%;
    display: block;
    clear: both;
    border: solid 1px #aaa;
    margin: 0.5em 0;
    padding: 4px;
    background: #eff1f3dd;
    overflow: hidden;
    box-shadow: 3px 3px 3px #000;
	border-radius: 1em;
	
}
.comment img.fb-user{
    float: left;
    border-radius: 50%;
    margin-right: 5px;
    height: 50px;
    width: 50px;
}
.comment img.fb-sticker{
	margin: 5px auto;
    height: 100px;
    clear: both;
    display: block;
}
.comment b{
	color: #3d6ad6;
}
</style>
</head>
<body>
<div id="chats">

</div>
<script>
function ocultar(idli){
	setTimeout(function(){ $("#chats #"+idli).fadeOut(); }, <?php echo $config['pause']; ?> );
}
function recargar(){
	$.ajax({
	  method: "GET",
	  url: "<?php echo basename(__FILE__) ?>",
	  dataType: "json",
	  data: { format: "json" }
	}).done(function( msg ) {

		$.each(msg, function(key, value) {
			if ( ! $("#"+value['id']).length ) {
				$('#chats').prepend('<div class="comment" id="'+value['id']+'"><img class="fb-user" src="'+value['picture']+'" width="50" /><p><b>'+value['name']+':</b> '+value['message']+'</p></div>');	
				<?php
				if ($config['pause'] >= 1)
				echo "ocultar(value['id']);";
				?>
			}
            
        });
        
	});
}

$(document).ready(function(){
recargar();
setInterval( recargar, <?php echo $config['refresh']; ?> );
});
</script>
</body>
</html>
