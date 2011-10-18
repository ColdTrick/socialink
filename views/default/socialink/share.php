<?php 

	if($user_guid = get_loggedin_userid())

	if($networks = socialink_get_user_networks($user_guid)){
		
		if($vars["current_url"]){
			$current_url = $vars["current_url"];
		} else {
			$current_url = urlencode(current_page_url());	
		}
		
		$entity = $vars["entity"];
		
		echo "<div id='socialink_share_actions'>";
		foreach($networks as $network){
			
			echo "<div><a href='" . $vars["url"] . "pg/socialink/share?network=" . $network ."&url=" . $current_url . "'><img src='" . $vars["url"] . "mod/socialink/_graphics/" . $network . "/login.png'>" . elgg_echo("socialink:share:" . $network) . "</a></div>";
		}	
		echo "</div>";
		
		?>
		
		<script type="text/javascript">
			$(document).ready(function(){
				$("#socialink_share_actions a").click(function() {
					var title = document.title;
					var url = $(this).attr("href") + "&title=" + title; 
					
					$.fancybox({
						'href'	: url,
						'type'	: 'ajax'						
					});

					return false;
				});
			});			
		</script>
		
		<?php 
	}

?>