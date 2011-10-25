<?php 

	if($user_guid = elgg_get_logged_in_user_guid()){
	
		if($networks = socialink_get_user_networks($user_guid)){
			// load fancybox
			elgg_load_js("lightbox");
			elgg_load_css("lightbox");
			
			if($vars["current_url"]){
				$current_url = $vars["current_url"];
			} else {
				$current_url = urlencode(current_page_url());	
			}
			
			$entity = $vars["entity"];
			
			echo "<div id='socialink_share_actions'>";
			foreach($networks as $network){
				echo "<div>";
				echo "<a href='" . $vars["url"] . "pg/socialink/share?network=" . $network ."&url=" . $current_url . "'>";
				echo "<img src='" . $vars["url"] . "mod/socialink/_graphics/" . $network . "/login.png' />";
				echo elgg_echo("socialink:share:" . $network);
				echo "</a>";
				echo "</div>";
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
	}
