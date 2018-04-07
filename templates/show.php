<?php
	//Preventing from direct access
	defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<?php //require_once( dirname(__FILE__) . '/screen-meta.php'); ?>
<div class="wrap">
	<h1>Pages <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="page-title-action">Add New</a> <a href="#TB_inline?width=600&height=400&inlineId=page-tree-add-multiple" id="wp-pages-advanced-add-multiple" class="page-title-action thickbox" title="Add Multiple">Add Multiple</a></h1>
	<ul class="subsubsub">
		<?php
			$views = array();
			$activeClass = empty($_GET['post_status'])?' class="current"': '';
			echo '<li class="all"><a href="' . admin_url('edit.php?post_type=page') . '"' . $activeClass . '>All</a> |</li>';
			foreach( $get_post_stati as $class => $view ){
				$label = $view->label;
				if( !in_array($label, ['auto-draft', 'inherit'])){
					$views[ $class ] = "\t<li class='$class'><a href=\"" . admin_url('edit.php?post_status=' . $class . '&post_type=page') . "\">$label</a>";
				}
			}
			echo implode( " |</li>\n", $views ) . "</li>\n";
		?>
		<li style="margin-left: 15px; vertical-align: top;">
			<button type="submit" class="button button-primary" value="Filter" disabled>Save Changes</button>
		</li>
		<form method="get" action="edit.php" class="search-box">
			<input type="hidden" name="post_status" class="post_status_page" value="all">
			<input type="hidden" name="post_type" class="post_type_page" value="page">
			<label class="screen-reader-text" for="post-search-input">Search Pages:</label>
			<input type="search" id="post-search-input" name="s" value="">
			<input type="submit" id="search-submit" class="button" value="Search Pages">
		</form>
	</ul>
	<div>
		<?php $this->get(); $this->hierarchy( 0 ); ?>
	</div>
</div>
