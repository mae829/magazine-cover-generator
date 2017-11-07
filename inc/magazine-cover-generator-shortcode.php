<?php

class Magazine_Cover_Generator_Shortcode {

	static $instance = false;

	/**
	 * Singleton
	 *
	 * Returns a single instance of the current class.
	 */
	public static function singleton() {

		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	public function __construct() {

		add_shortcode( 'cover_generator', array( $this, 'cover_generator_func' ) );

	}

	public function cover_generator_func( $atts ) {

		// override default attributes with user attributes
		$atts = shortcode_atts( array(
			'invertPointX' => 0,
			'invertPointY' => 165,
		), $atts, 'cover_generator' );

		// we need the styles everywhere the shortcode is being generated
		wp_enqueue_style( 'magazine-cover-generator-css' );

		// start getting content of shortcode ready
		ob_start();

		echo '<p><b>Step 1.</b> Upload your photo. Must be smaller than '. size_format( MCG_MAX_UPLOAD_SIZE, 0 ). '. Vertical Images work best(portrait/vertical/selfie works better than landscape).</p>';

		// If not on submit, or missing image, or nonce does not verify, show the form.
		if ( !isset( $_POST['submit'] ) || !isset( $_POST['mcg_image_form_submitted'] ) || !wp_verify_nonce( $_POST['mcg_image_form_submitted'], 'mcg_image_form') ) {

			wp_enqueue_script( 'magazine-cover-generator-js' );

			echo $this->get_form();

			return ob_get_clean();

		}

		// validate image
		$result	= $this->validate_file( $_FILES['mcg_cover_image'] );

		if ( !empty( $result['error'] ) ) {

			wp_enqueue_script( 'magazine-cover-generator-js' );

			echo '<p class="mcg-error">ERROR: ' . $result['error'] . '</p>';
			echo '<p class="mcg-error">Please try again.</p>';

			echo $this->get_form();

			return ob_get_clean();

		}

		// check the processed image
		$user_image_source	= $this->handle_user_image( $_FILES['mcg_cover_image'] );

		if ( empty( $user_image_source ) ) {

			wp_enqueue_script( 'magazine-cover-generator-js' );

			echo '<p>Sorry, did not recognize the file type you tried to upload. Please try again.</p>';

			echo $this->get_form();

			return ob_get_clean();

		}

		/**
		 * After validation and error checks, now lets show our controls and processed image
		 */

		// enqueue scripts now since we will definitely be showing them
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ) );
		wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris' ) );
		wp_enqueue_script( 'fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/1.7.12/fabric.min.js' );
		wp_enqueue_script( 'magazine-cover-generator-js' );

		$colorpicker_l10n = array(
			'clear'			=> __( 'Clear' ),
			'defaultString'	=> __( 'Default' ),
			'pick'			=> __( 'Select Color' ),
			'current'		=> __( 'Current Color' ),
		);
		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

		$covers_list	= cover_generator_get_option( 'covers_list' ) ? cover_generator_get_option( 'covers_list' ) : array();
		$covers_ids		= array_keys( $covers_list );

		$bg_colors		= cover_generator_get_option( 'bg_colors' ) ? cover_generator_get_option( 'bg_colors' ) : '';

		$first_cover_data	= wp_get_attachment_image_src( $covers_ids[0], 'full' );
		$first_cover_url	= $first_cover_data[0];
		$first_cover_width	= $first_cover_data[1];
		$first_cover_height	= $first_cover_data[2];
		?>

		<script>
			// color picker palette
			var colorPickerPalette = ["<?php echo implode( '","', $bg_colors ); ?>"];

			// defaults for editor
			var mask	= {
				src: '<?php echo $first_cover_url; ?>',
				left: 'center',
				top: 'center',
				width: <?php echo $first_cover_width; ?>,
				height: <?php echo $first_cover_height; ?>,
				opacity: 1,
				selectable: false,
			};
			var userImage	= {
				zIndex: 0,
				name: 'user-uploaded',
				controlTitle: 'Uploaded Image',
				type: 'image',
				src: 'data:image/jpeg;base64,<?php echo base64_encode( $user_image_source ); ?>',
				lockScalingFlip: true,
				lockUniScaling: true,
				angle: 0,
				opacity: 1,
				scale: 1,
				evented: true,
				hasControls: true,
				selectable: true,
				rotationIncrement: 15,
				movementIncrement: 15,
				scaleIncrement: 0.05
			}
			var invertPoint	= {
				x: <?php echo $atts['invertPointX']; ?>,
				y: <?php echo $atts['invertPointY']; ?>
			}

		</script>

		<div class="main-editor-content">

			<div id="img-edit">

				<div id="canvas__container">

					<canvas id="canvas"></canvas>

				</div>

				<!-- CONTROLS HERE -->
				<form action="" class="canvas__controls">

					<?php if ( !empty( $bg_colors ) ) { ?>

						<fieldset class="canvas__controls-subgroup">
							<label for="canvasbg"><strong>Step 2.</strong> Select Background Color</label>
							<input type="text" name="canvasbg" class="color-field" value="" />
						</fieldset>

					<?php } ?>

					<fieldset class="canvas__controls-subgroup">

						<p><strong>(Optional)</strong> Invert Headlines Color</p>

						<div class="inverttext">
							<input type="checkbox" name="inverttext" id="inverttext" class="inverttext-checkbox">
							<label for="inverttext" class="inverttext-label"></label>
						</div>

					</fieldset>

					<fieldset class="canvas__controls-subgroup canvas__masks">

						<h4><strong>Step 3.</strong> Select Cover Style</h4>

						<div class="canvas__masks__wrapper">

							<?php foreach ( $covers_ids as $key => $cover_id ) {

								$cover_medium	= wp_get_attachment_image_src( $cover_id, 'medium' )[0];
								$cover_full		= wp_get_attachment_image_src( $cover_id, 'full' )[0];
								$checked		= $key == 0 ? ' checked' : '';
								$input_name		= 'canvasmask'. $key;

								echo '<input type="radio" name="canvasmask" id="'. $input_name .'" value="'. $cover_full .'"'. $checked .'>';
								echo '<label for="'. $input_name .'"><img src="'. $cover_medium .'"></label>';

							} ?>

						</div>

					</fieldset>

				</form>

			</div>

			<div id="no-img-edit" style="display: none;">
				<img src="data:image/jpeg;base64,<?php echo base64_encode( $user_image_source ); ?>" style="max-width: 393px; max-height: 325px;" />
			</div>

			<a id="btn-download" class="btn-submit" href="#">Download Your Cover!</a>

		</div>

		<noscript>
			<style type="text/css">
				.main-editor-content {
					display: none;
				}

				#noscriptdiv {
					display: block;
					text-align: left;
				}
			</style>

			<div id="noscriptdiv">

				<div class="inner-width">
					<h2>JavaScript must be enabled</h2>
					<p>Please enable JavaScript in your browser to start your endorsement. Consult your browser’s Help section for information on how to change the JavaScript settings, then click Continue below.</p>
				</div>
			</div>
		</noscript>

		<?php

		return ob_get_clean();

	}

	/**
	 * Get the upload form markup
	 * @return string	Full markup of the form
	 */
	public function get_form() {

		ob_start(); ?>

			<form name="mcg-cover" method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'mcg_image_form', 'mcg_image_form_submitted' ); ?>
				<input type="file" name="mcg_cover_image" id="mcg_cover_image">
				<input name="submit" type="submit" value="Upload Image" class="btn-submit">
			</form>

		<?php
		return ob_get_clean();

	}

	public function handle_user_image( $file_upload = null ) {

		if ( $file_upload === null ) {
			return null;
		}

		$image_name	= $file_upload['name'];
		$image_size	= $file_upload['size'];
		$image_temp	= $file_upload['tmp_name'];
		$image_type	= $file_upload['type'];

		switch ( strtolower( $image_type ) ) { //determine uploaded image type
			// Create new image from file
			case 'image/png':
				$image_resource	= imagecreatefrompng( $image_temp );
				break;
			case 'image/gif':
				$image_resource	= imagecreatefromgif( $image_temp );
				break;
			case 'image/jpeg':
			case 'image/pjpeg':
				$image_resource	= imagecreatefromjpeg( $image_temp );
				break;
			default:
				$image_resource	= false;
		}

		list( $img_width, $img_height )	= getimagesize( $image_temp );

		$new_canvas	= imagecreatetruecolor( $img_width , $img_height );

		if ( imagecopyresampled( $new_canvas, $image_resource , 0, 0, 0, 0, $img_width, $img_height, $img_width, $img_height) ) {

			// capture image so we can display it
			ob_start();
			imagejpeg( $new_canvas, NULL , 100 );

			// free up memory
			imagedestroy( $new_canvas );
			$user_image_source	= ob_get_clean();

		}


		return $user_image_source;

	}

	public function validate_file( $file = '' ) {

		$result				= array();
		$result['error']	= false;

		if ( $file['error'] ) {

			$result['error']	= 'No file uploaded or there was an error during the upload.';

			return $result;

		}

		$image_data	= getimagesize( $file['tmp_name'] );

		$maximum	= array(
			'width'		=> '4048',
			'height'	=> '3036'
		);
		$image_width	= $image_data[0];
		$image_height	= $image_data[1];

		if ( !in_array( $image_data['mime'], unserialize( MCG_TYPE_WHITELIST ) ) ) {

			$result['error'] = 'Your image must be a jpeg or png.';

		} elseif ( $file['size'] > MCG_MAX_UPLOAD_SIZE ) {

			$file_size	= size_format( $file['size'], 2 );
			$max_size	= size_format( MCG_MAX_UPLOAD_SIZE, 2 );

			$result['error'] = 'Your image was '. $file_size .'! It must not exceed '. $max_size .'.';

		} elseif (  $image_width > $maximum['width'] || $image_height > $maximum['height'] ) {
			$result['error']	= "Image dimensions are too large. Maximum size is {$maximum['width']} by {$maximum['height']} pixels. Uploaded image is $image_width by $image_height pixels.";
		}

		return $result;

	}

}
