<?php
/*
	Plugin Name: MM Asset Widget
	Plugin URI: https://www.makematic.com/staff/assets-manager
	Description: An widget to display resources for displayed asset
	Version: 1.0
	Author: Rachael Harkin
	Author URI: http://www.hybrid.digital
	Text Domain: mm-asset-widget
	License: GPL3
*/
defined( 'ABSPATH' ) or die( 'Unauthorised Access' );

add_action( 'widgets_init', function(){	register_widget( 'MM_Asset_Widget' ); } );

// Creating the widget
  class MM_Asset_Widget extends WP_Widget {

    function __construct() {
      $this->requires();
      $this->hooks();

      $this->s3ResUrl     = BUCURL;
      $this->audioSubDir  = '/audio/';
      $this->imgSubDir    = '/images/';
      $this->docsSubDir   = '/docs/';
      $this->vidSubDir    = '/video/';
      $this->currentUser  = wp_get_current_user();

    	$widget_ops = array(
  			'classname' => 'MM_Asset_Widget',
  			'description' => 'This widget displays the asset resources for the currently displayed asset',
  		);
  		parent::__construct( 'MM_Asset_Widget', 'MM Asset Widget', $widget_ops );
    }

    public function mm_asset_widget_scripts() {
      // js files
       wp_register_script( 'mmawidget-script', plugins_url('js/mmawidget-script.js',__FILE__ ), array('jquery'), '1.0.0', true );
       wp_enqueue_script( 'mmawidget-script');

       // css files
       wp_register_style( 'mmawidget-styles', plugins_url('css/mmawidget-styles.css',__FILE__ ) );
       wp_enqueue_style( 'mmawidget-styles');
    }

  /**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
    public function widget( $args, $instance ) {
      $content = "";

      $queried_object = get_queried_object();
      // setting defaults for $asset items
      $wpasset = new stdClass();
      $wpasset->ID = 0;
      $wpasset->post_name= 'default-asset';
      //$this->s3ResUrl.$wpasset->assetDir.$this->vidSubDir.[filename]
      if( empty( $instance['title'] ) ){ $instance['title']="RESOURCES"; }

      // getting current page widget is displayed on.
      if ( $queried_object ) {
          $wpasset = $queried_object;
      }
      // define location for remote asset directory.
      $wpasset->assetDir = $wpasset->ID.'_'.$wpasset->post_name;

      $title = apply_filters( 'widget_title', $instance['title'] );
      echo $args['before_widget'];

      if( is_user_logged_in() ){
        $memberships = new MM_Assets_LLMS_Memberships();
        //$membership = $memberships->UserHasMembership( get_current_user_id() );
        //echo "WIDGET CALL:::".print_r($membership['_has_membership']);

          //  checking for licence here.
          if( ( in_array('administrator',$this->currentUser->roles) ) || ( $memberships->isUserEnrolled( $wpasset->ID) == 'is-enrolled' ) ){
            // before and after widget arguments are defined by themes
            if ( ! empty( $title ) ){
              echo $args['before_title'] . $title . $args['after_title'];
            }
            $content .= '<div id="mm-asset-widget">';
            $content .= '<div class="mm-container">';
            $content .= $this->buildVideoList($wpasset);
            $content .= $this->buildAudioList($wpasset);
            $content .= $this->buildImageList($wpasset);
            $content .= $this->buildDocsList($wpasset);
          }else{
            $content .= '<div class="widget" id="message"><br><p>Purchase a licence and gain further access to more resources for this course.</p>';
            $content .= '<br><a class="llms-button-action button" href="/become-a-member">I WANT A LICENCE</a></div>';
        }
      }
      $content .= '</div>';
      echo __( $content, 'MM_Asset_Widget' );
      echo $args['after_widget'];
    }

    /**
  	 * Outputs the options form on admin
  	 *
  	 * @param array $instance The widget options
  	 */
    public function form( $instance ) {
      parent::form( $instance );

      if ( isset( $instance[ 'title' ] ) ) {
        $title = $instance[ 'title' ];
      }
      else {
      $title = __( 'New title', 'MM_Asset_Widget' );
      }
    // Widget admin form
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
    }

    /**
  	 * Processing widget options on save
  	 *
  	 * @param array $new_instance The new options
  	 * @param array $old_instance The previous options
  	 *
  	 * @return array
  	 */
    public function update( $new_instance, $old_instance ) {
      $instance = array();
      $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
      return $instance;
    }

    //=======================================================================================================//
    //=========================================== HELPER METHODS ==========================================//
    private function hooks(){
    //  if( $type == 'course' ){ // only load js files for wodget when inside course pages
        add_action( 'wp_enqueue_scripts', array( $this, 'mm_asset_widget_scripts' ) );
    //  }
    }

    private function requires(){
      require_once ABSPATH.'wp-content/plugins/mm-asset-widget/config.php';
      require_once ABSPATH.'wp-content/plugins/mm-asset-widget/classes/class.aws-resources.php'; // to check memberships#
      require_once ABSPATH.'wp-content/plugins/mm-asset-widget/classes/class.llms-memberships.php'; // to check memberships#
    }

    private function prepareS3FileObject($folder=NULL){
      $s3Objects = new AWS_GetResources;
      return $s3Objects->getResources($folder);
    }

    private function buildAudioList($wpasset=NULL){
      $list = "";
      $s3FileList = $this->prepareS3FileObject($wpasset->assetDir.$this->audioSubDir);

      $list .=  '<div id="audio-list" class="item-list"><h4>Audio URLs<span class="list-open">+</span></h4>';
      $list .= '<div id="container-2" class="mmarw-container">';
      $list .= '<ul>';

      foreach ( $s3FileList as $a ){
        $meta = $this->buildAssetMeta($a);
        $type = pathinfo($meta->file);

        $list .=  '<li><span class="type">'.$type['extension'].'</span><a href="'.$this->s3ResUrl.'/'.$meta->full_path.'">'.$meta->display_fileName.'</a></li>';
      }
      $list .= '</ul></div></div>';
      return $list;
    }

    private function buildImageList($wpasset=NULL){
      $s3FileList = $this->prepareS3FileObject($wpasset->assetDir.$this->imgSubDir);
      $list = "";
      $list .= '<div id="image-list" class="item-list"><h4>Images<span class="list-open">+</span></h4>';
      $list .= '<div id="container-3" class="mmarw-container">';
      $list .= '<ul>';

      foreach ( $s3FileList as $a ){
        $meta = $this->buildAssetMeta($a);
        $type = pathinfo($meta->file);
        if( strpos( $meta->file,'png') !== false ){ $class ='png'; }
        if( ( strpos( $meta->file,'jpeg') !== false ) || ( strpos( $meta->file,'jpg') !== false) ){ $class ='jpg';}
        if( strpos( $meta->file,'gif') !== false ){ $class ='gif';}
        if( strpos( $meta->file,'tiff')!== false ){ $class ='tiff';}

        $list .=  '<li class="'.$class.'"><span class="type">'.$type['extension'].'</span><a href="'.$this->s3ResUrl.'/'.$meta->full_path.'">'.$meta->display_fileName.'</a></li>';
      }
      $list .=  '</ul></div></div>';

      return $list;
    }

    private function buildDocsList($wpasset=NULL){
      $list=""; $class="doc";

      $s3FileList = $this->prepareS3FileObject($wpasset->assetDir.$this->docsSubDir);
      $list .= '<div id="docs-list" class="item-list"><h4>Documents<span class="list-open">+</span></h4>';
      $list .= '<div id="container-4" class="mmarw-container">';
      $list .= '<ul>';

      foreach ( $s3FileList as $a ){
        $meta = $this->buildAssetMeta($a);
        $type = pathinfo($meta->file);
        if( strpos( $meta->file,'docx')!== false ){ $class ='doc';}
        if( strpos( $meta->file,'pdf') !== false ){ $class ='pdf';}
        if( strpos( $meta->file,'odt')!== false ){ $class ='odt';}
        if( strpos( $meta->file,'xls')!== false ){ $class ='xls';}
        if( strpos( $meta->file,'txt')!== false ){ $class ='txt';}
        if( strpos( $meta->file,'rtf')!== false ){ $class ='rtf';}

        $list .=  '<li class="'.$class.'"><span class="type">'.$type['extension'].'</span><a href="'.$this->s3ResUrl.'/'.$meta->full_path.'">'.$meta->display_fileName.'</a></li>';
      }
      $list .=  '</ul></div></div>';
      return $list;
    }

    private function buildVideoList($wpasset=NULL){
      $list = "";
      $s3FileList = $this->prepareS3FileObject( $wpasset->assetDir.$this->vidSubDir);
      $list .= '<div id="video-list" class="item-list"><h4>Video Embed Code<span class="list-open">+</span></h4>';
      $list .= '<div id="container-1" class="mmarw-container">';

      foreach ( $s3FileList as $a ){
        $meta = $this->buildAssetMeta($a);

        // if the 'Key' string contains asset->ID and 'video' and file types mp4,oog,webM
        if( (    ( strpos($meta->full_path, "mp4") !== false )
              || ( strpos($meta->full_path, "ogg") !== false )
              || ( strpos($meta->full_path, "mov") !== false )
              || ( strpos($meta->full_path, "flv") !== false )
              || ( strpos($meta->full_path, "wmv") !== false )
              || ( strpos($meta->full_path, "avi") !== false )
              || ( strpos($meta->full_path, "webM") !== false )
            )
            && ( $meta->asset_id == $wpasset->ID )
          ){

              if( strpos( $meta->file,'mp4') !== false ){ $type ='mp4'; }
              if( strpos( $meta->file,'ogg') !== false ){ $type ='ogg'; }
              if( strpos( $meta->file,'mov') !== false ){ $type ='mov'; }
              if( strpos( $meta->file,'flv') !== false ){ $type ='flv'; }
              if( strpos( $meta->file,'avi') !== false ){ $type ='avi'; }
              if( strpos( $meta->file,'wmv') !== false ){ $type ='wmv'; }
              if( strpos( $meta->file,'webM') !== false ){ $type ='webM'; }

              //
              //.WP_HOME.'check-licence?type=video&aid='.$wpasset->ID.'&uid='.$this->currentUser->ID.'&src='.$meta->full_path.
              $list .= '<h5>'.$meta->display_fileName.'</h5>
                  <textarea id="video-'.$wpasset->ID.'">
                    <video width="320" height="240" controls>
                    <source src="'.$this->s3ResUrl.'/'.$meta->fullpath_without_file_extension.'.'.$type.' type="video/'.$type.'">
                    Your browser does not support the video tag. Please find the correct browser for support of this video tag here: https://www.w3schools.com/html/html5_video.asp
                    </video>
                  </textarea>';
        }
      }
      $list .= '</div></div>'; // close .mmarw-container & .widget
      return $list;
    }

    private function buildAssetMeta( $s3asset=NULL ){

      $meta = new stdClass;
      $meta->asset_id                        = 0;
      $meta->coursename_slug                 = "";
      $meta->full_path                       = $s3asset['Key'];
      $meta->fullpath_without_file_extension = substr( $s3asset['Key'], 0, -4 ); // full filepath without file extension;

      if( isset( $s3asset['Key'] ) &&  ( strpos( $s3asset['Key'],"_" ) !== false ) ){ // need this check to prevent "undefined offset: 1" error when the character doesnt exist in the string using the 'explode' function.

        // we bind the returning results to the listed variables with the "list" method.
        list( $meta->asset_id, $file ) = explode( '_', $s3asset['Key'], 2 ); // array of strings [ assetid, filename,]
        $fname = explode( '/', $file );
        list( $assetId,
              $fileId,
              $meta->display_fileName ) = explode( '_', $fname[2] );

        $meta->file            = $fname[2];
        $meta->coursename_slug = $fname[0];
        $meta->nice_coursename = str_replace( '-',' ',$fname[0] );

      }
      return $meta;
    }

} // Class ends
?>
