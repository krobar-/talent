<?php
if (!class_exists("TalentDisplay"))
{


class TalentDisplay
	extends TalentMain
{	
	
	public static function adminCSS()
	{
		global $post, $post_type;
		
		?>
		<style type="text/css">
			#icon-talent {
				background: transparent url('<?php echo( Talent::$url->plugin ); ?>lib/images/icon32.png') center center no-repeat;
			}
			#adminmenu #toplevel_page_talent div.wp-menu-image {
				background: transparent url('<?php echo( Talent::$url->plugin ); ?>lib/images/menu.png') no-repeat 1px -33px;
			}
			#adminmenu #toplevel_page_talent:hover div.wp-menu-image,
			#adminmenu #toplevel_page_talent.wp-has-current-submenu div.wp-menu-image {
				background-position: 1px -1px;
			}
			textarea[readonly] {
				background-color: #eee;
			}
			.person-options {
				display: none;
			}
			#talent-person fieldset {
				width: 98%;
				margin-bottom: 5px;
				padding: 5px;
				border: 2px solid #bbb;
				border-radius: 4px;
			}
			#talent-person fieldset fieldset {
				border-width: 1px;
				background-color: #FBFBFE;
			}
			#tcd-person-headshot,
			#tcd-ajax-loader,
			.tcd-auxillary-preview {
				display: block;
				margin: 0 auto;
				width: <?php echo( Talent::getOption('img_thumb_width') ); ?>px;
				height: <?php echo( Talent::getOption('img_thumb_height') ); ?>px;
				background-color: transparent;
				background-repeat: no-repeat;
				background-position: center center;
				/*background-image: url();*/
			}
			#tcd-person-headshot,
			.tcd-auxillary-preview {
				background-size: cover;
			}
			.tcd-auxillary-image {
				display: inline-block;
				width: <?php echo( Talent::getOption('img_thumb_width') ); ?>px;
				height: auto;
			}
			#tcd-ajax-loader {
				display: none;
				background-image: url(<?php echo( Talent::$url->plugin ); ?>images/ajax_loader_gray_48.gif);
			}
			.tcd-upload-button {
				opacity: 0;
				width: 100px;
				position: absolute;
				top: 0;
				left: 0;
				z-index: 100;
			}
			.tcd-clothing-set label span,
			.tcd-label {
				display: inline-block;
				width: 100px;
				text-align: right;
				padding-right: 6px;
			}
			.tcd-label.no-bump {
				width: auto;
			}
		</style>
		<?php
	}

	public static function table( $message = FALSE )
	{

		$table = new TalentListings();
		$newItem = 'person';

		$table->prepare_items();
		
		$new_link = sprintf( '<a href="?page=%s&action=%s" class="add-new-h2">Add New</a>',	$_REQUEST['page'], "new-" . $newItem . Talent::createURLNonce() );
		 
		?>
		<div class="wrap">
		
			<div id="icon-talent" class="icon32"><br/></div>
			<h2><?php echo( "Talent ". $new_link) ?></h2>
		
			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
			<form id="person-filter" method="GET">
				<!-- For plugins, we also need to ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php echo( Talent::createNonce() . "\n" ); ?>
				<!-- Now we can render the completed list table -->
				<?php $table->display() ?>
				</form>
		
		</div>
		<?php
	}

	public static function editor( $person, $action, $next_action )
	{
		

		switch ( $action )
		{
			case 'create':
			case 'update':
			case 'edit':
				$_title = "Edit Person";
				$addtl_images = TalentPerson::initAllLinkedImages( $person->id );
				break;
			case 'new-person':
				$_title = "New Person";
				break;
			default:
				return;
		}
		// Form Preparation

		$_nonce = Talent::createNonce( 'edit person' );
		$_pidField = ( isset( $person->id ) ) ? "<input id=\"tcd-person-id\" type=\"hidden\" name=\"tcd_person_id\" value=\"{$person->id}\" />" : '';

		// Option Fields
		$eye_options = '';
		foreach( TalentPerson::$eye_colors as $key => $value )
		{
			$eye_selected = ( $person->color_eye == $key ) ? ' selected="selected"' : '';
			$eye_options .= "\t\t\t\t\t<option value=\"$key\"{$eye_selected}>$value</option>\n";
		}
		$hair_options = '';
		foreach( TalentPerson::$hair_colors as $key => $value )
		{
			$hair_selected = ( $person->color_hair == $key ) ? ' selected="selected"' : '';
			$hair_options .= "\t\t\t\t\t<option value=\"$key\"{$hair_selected}>$value</option>\n";
		}
		$race_options = '';
		foreach( TalentPerson::$races as $key => $value )
		{
			$race_selected = ( $person->ethnicity == $key ) ? ' selected="selected"' : '';
			$race_options .= "\t\t\t\t\t<option value=\"$key\"{$race_selected}>$value</option>\n";
		}

		$gender_options = '';
		if( FALSE == $person->gender && NULL !== $person->gender  )
		{
			$male_selected = ' selected="selected"';
		}
		elseif ( TRUE == $person->gender ) {
			$female_selected = ' selected="selected"';
		}
		$gender_options .= "\t\t\t\t\t<option value=\"0\"$male_selected>Male</option>";
		$gender_options .= "\t\t\t\t\t<option value=\"1\"$female_selected>Female</option>";

		// additional images
		$additional_images = '';
		if( isset( $person->id ) )
		{
			$_addtlImages = TalentPerson::initAllLinkedImages( $person->id );

			$_html = array();
			$i = 0;
			foreach( $_addtlImages as $_image )
			{
				$_preview_thumb = TalentUpload::$upload_url . $_image->image_thumb;
				// id, person_id, image, image_thumb, image_title, image_description
				$_html[] = <<<IMAGEHTML

			<div id="tcd-aux-{$i}" class="tcd-auxillary-image">
				<div id="tcd-aux-preview-{$i}" class="tcd-auxillary-preview" style="background-image: url({$_preview_thumb});"></div>
				<input id="tcd-image-id-{$i}" type="hidden" name="tcd_image[{$i}][id]" value="{$_image->id}" />
				<input id="tcd-image-{$i}" type="hidden" name="tcd_image[{$i}][full]" value="{$_image->image}" />
				<input id="tcd-image-thumb-{$i}" type="hidden" name="tcd_image[{$i}][thumb]" value="{$_image->image_thumb}" />
				<label for="tcd-image-title-{$i}"><input id="tcd-image-title-{$i}" type="text" name="tcd_image[{$i}][title]" value="{$_image->image_title}" /></label><br>
				<label for="tcd-image-description-{$i}">Description<br>
					<textarea id="tcd-image-description-{$i}" class="small-text" name="tcd_image[{$i}][description]" rows="3">{$_image->image_description}</textarea>
				</label><br>
			</div>

IMAGEHTML;
				$i++;
			}

			$additional_images = implode( "\n", $_html );

		}

		// WordPress will not insert null into the database, so we have to test for 0000-00-00 00:00:00
		$parsed_birthdate = ( empty($person->birthdate) || '0000-00-00 00:00:00' === $person->birthdate ) ? NULL : date( "Y-m-d", strtotime( $person->birthdate  ) );

		$person_thumbnail = ( !empty($person->image_headshot_thumb) && self::file_url_exists( TalentUpload::$upload_url . $person->image_headshot_thumb ) ) ? 'style="background-image: url(' . TalentUpload::$upload_url . $person->image_headshot_thumb . ');"' : 'style="background-image: url(' . Talent::$url->plugin .'images/person-icon.png);"';

		$person_age = ($person->use_birthdate) ? TalentPerson::parseAge($parsed_birthdate, 'age') : $person->age;
		// Start Form Output
		?>
		<div class="wrap">
			
			<div id="icon-talent" class="icon32"><br/></div>
			<h2><?php echo( $_title ) ?></h2>
			<div id="notice" class="below-h2"></div>
					
			<form name="talent-person" action="?page=<?php echo( $_REQUEST['page'] ) ?>&action=<?php echo( $next_action ) ?>" enctype="multipart/form-data" method="post" id="talent-person">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php echo( $_nonce . "\n" . $_pidField . "\n" ); ?>
				
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						
						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables" class="meta-box-sortables">
								
								<div id="project-save-div" class="postbox " >
									<div class="handlediv" title="Click to toggle"><br /></div>
									<h3 class='hndle'><span>Save Record</span></h3>
									<div class="inside">
										
										<div style="width: 100%; text-align:right;">
											<input class="button-secondary button-large" type="submit" name="submit" value="Cancel">
											<input class="button-primary button-large" type="submit" name="submit" value="Save">
										</div>
									
									</div><!-- /inside -->
								</div><!-- /person-save-div -->
							
								<div id="person-settings-div" class="postbox " >
									<div class="handlediv" title="Click to toggle"><br /></div>
									<h3 class='hndle'><span>Headshot</span></h3>
									<div class="inside">
									
										<fieldset id="tcd-headshot" class="" form="talent-person">
											<div id="tcd-person-headshot"<?php echo( $person_thumbnail ); ?>>
												<div id="tcd-ajax-loader">
												</div>
											</div>
											<!-- Image holder fields -->
											<input id="tcd-image-headshot" type="hidden" name="tcd_image_headshot" value="" />
											<input id="tcd-image-headshot-thumb" type="hidden" name="tcd_image_headshot_thumb" value="" />
											<!-- AJAX upload -->
											<span id="upload-button" class="button-primary button-large" style="position: relative;">
												Upload Image
												<span id="tcd-headshot-upload-input"><input type="file" id="headshot-upload" class="tcd-upload-button" name="headshot_upload" size="20" /></span>
											</span>
										</fieldset>
									</div><!-- /inside -->
								</div><!-- /person-settings-div -->
								
							</div><!-- /side-sortables -->
						</div><!-- /postbox-container-1 -->
						
						<div id="postbox-container-2" class="postbox-container">
							<div id="normal-sortables" class="meta-box-sortables ui-sortable">
							
								<div id="tcd-person-settings" class="postbox" style="display: block;">
									<div class="handlediv" title="Click to toggle"><br /></div>
									<h3 class='hndle'><span>Information</span></h3>
									<div class="inside">
									
										<fieldset form="talent-person">
											<legend><h4>Basic Information</h4></legend>

											<fieldset form="talent-person">
												<legend><h5>Name</h5></legend>

												<input id="tcd-name-first" class="medium-text" type="text" name="tcd_name_first" value="<?php echo( $person->name_first ) ?>" placeholder="First" />
												<input id="tcd-name-middle" class="small-text" type="text" name="tcd_name_middle" value="<?php echo( $person->name_middle ) ?>" placeholder="M" />
												<input id="tcd-name-last" class="medium-text" type="text" name="tcd_name_last" value="<?php echo( $person->name_last ) ?>" placeholder="Last" />
											</fieldset>

											<fieldset form="talent-person">
												<legend><h5>Vital Statistics</h5></legend>
												<label for="tcd-birthdate"><span class="tcd-label no-bump">Birthdate:</span><input id="tcd-birthdate" class="medium-text" type="date" name="tcd_birthdate" value="<?php echo( $parsed_birthdate ) ?>" /></label>
												<label for="tcd-age"><span class="tcd-label">Age:</span><input id="tcd-age" class="small-text" type="number" name="tcd_age" size="3" min="0" value="<?php echo( $person_age ) ?>" placeholder="Age" /></label>
												<label for="tcd-gender"><span class="tcd-label">Sex:</span>
												<select id="tcd-gender" name="tcd_gender">
													<option value="">Select One</option>
													<?php echo( $gender_options ); ?>
												</select>
												</label><br/>
												<?php 
													$use_birthdate_checked = ( $person->use_birthdate > 0 ) ? ' checked="checked"' : '' ;
												?>
												<label for="tcd-use-birthdate"><input id="tcd-use-birthdate" type="checkbox" name="tcd_use_birthdate" value="1"<?php echo( $use_birthdate_checked ); ?> /> Use Birthdate to Calculate Age.</label><br/>
											</fieldset>

											<fieldset id="tcd-guardian" form="talent-person">
												<legend><h5>Guardian:</h5></legend>
								
												<input id="tcd-guardian-first" class="medium-text" type="text" name="tcd_guardian_first" value="<?php echo( $person->guardian_first ) ?>" placeholder="First Name" />
												<input id="tcd-guardian-last" class="medium-text" type="text" name="tcd_guardian_last" value="<?php echo( $person->guardian_last ) ?>" placeholder="Last Name" />
												<input id="tcd-guardian-relation" class="medium-text" type="text" name="tcd_guardian_relation" value="<?php echo( $person->guardian_relation ) ?>" placeholder="Relationship" />				
											</fieldset>

											
										</fieldset><br/>

										<fieldset form="talent-person">
											<legend><h4>Representation</h4></legend>

											<fieldset form="talent-person">
												<legend><h5>Agency</h5></legend>

												<label for="tcd-agency-name" style="display: inline-block;"><span class="tcd-label no-bump">Name:</span>
												<input id="tcd-agency-name" class="medium-text" type="text" name="tcd_agency_name" value="<?php echo( $person->agency_name ) ?>" placeholder="" />
												</label>
												<label for="tcd-agency-number" style="display: inline-block;"><span class="tcd-label">Number:</span>
												<input id="tcd-agency-number" class="medium-text" type="tel" name="tcd_agency_number" value="<?php echo( $person->agency_number ) ?>" placeholder="###-###-####" />
												</label><br/><br/>
											</fieldset>

											<!--Unions-->
											<fieldset form="talent-person">
												<legend><h5>Union</h5></legend>
												<?php 
													$sag_checked = ( $person->union_sag > 0 ) ? ' checked="checked"' : '' ;
													$aftra_checked = ( $person->union_aftra > 0 ) ? ' checked="checked"' : '' ;
													$aea_checked = ( $person->union_aea > 0 ) ? ' checked="checked"' : '' ;

												?>
												<label for="tcd-union-sag"><input id="tcd-union-sag" type="radio" name="tcd_union_sag" value="1"<?php echo( $sag_checked ); ?> />SAG&nbsp;&nbsp;</label>
												<label for="tcd-union-aftra"><input id="tcd-union-aftra" type="radio" name="tcd_union_aftra" value="1"<?php echo( $aftra_checked ); ?> />AFTRA&nbsp;&nbsp;</label>
												<label for="tcd-union-aea"><input id="tcd-union-aea" type="radio" name="tcd_union_aea" value="1"<?php echo( $aea_checked ); ?> />AEA&nbsp;</label>
												<label for="tcd-union-sag-id"><span class="tcd-label">SAG ID</span>
												<input id="tcd-union-sag-id" class="medium-text" type="text" name="tcd_union_sag_id" value="<?php echo( $person->union_sag_id ) ?>"  />
												</label><br/>
											</fieldset>
										</fieldset><br/>

										<fieldset form="talent-person">
											<legend><h4>Contact Information</h4></legend>
											
											<fieldset form="talent-person">
												<legend><h5>Telephone</h5></legend>

												<label for="tcd-phone-primary"><span class="tcd-label">Primary:</span><input id="tcd-phone-primary" class="" type="tel" name="tcd_phone_primary" value="<?php echo( $person->phone_primary ) ?>" placeholder="###-###-####" /></label>
												<label for="tcd-phone-alternate"><span class="tcd-label">Alternate:</span><input id="tcd-phone-alternate" class="" type="tel" name="tcd_phone_alternate" value="<?php echo( $person->phone_alternate ) ?>" placeholder="###-###-####" /></label>
											</fieldset>

											<fieldset form="talent-person">
												<legend><h5>Internet</h5></legend>

												<label for="tcd-email-address"><span class="tcd-label">Email Address:</span><input id="tcd-email-address" class="" type="email" name="tcd_email_address" value="<?php echo( $person->email_address ) ?>" placeholder="example@domain.com" /></label>
											</fieldset><br/>

											<fieldset form="talent-person">
												<legend><h5>Postal</h5></legend>

												<label for="tcd-address" style="display: inline-block; width: 100%;">Address:<br/>
												<input id="tcd-address-street-1" class="large-text" type="text" name="tcd_address_street_1" value="<?php echo( $person->address_street_1 ) ?>" placeholder="Street 1" /><br/>
												<input id="tcd-address-street-2" class="large-text" type="text" name="tcd_address_street_2" value="<?php echo( $person->address_street_2 ) ?>" placeholder="Street 2" /><br/>
												<input id="tcd-address-city" class="medium-text" type="text" name="tcd_address_city" value="<?php echo( $person->address_city ) ?>" placeholder="City" />
												<input id="tcd-address-state" class="small-text" type="text" name="tcd_address_state" value="<?php echo( $person->address_state ) ?>" placeholder="State" />
												<input id="tcd-address-zipcode" class="medium-text" type="text" name="tcd_address_zipcode" value="<?php echo( $person->address_zipcode ) ?>" placeholder="Zipcode" /><br/>
												</label>
											</fieldset>

										</fieldset><br/>

										<fieldset form="talent-person">
											<legend><h4>Appearance</h4></legend>

											<fieldset form="talent-person">
												<label for="tcd-color-hair"><span class="tcd-label">Hair Color:</span>
												<select id="tcd-color-hair" name="tcd_color_hair">
													<option value="">Select One</option>
													<?php echo( $hair_options ); ?>
												</select>
												</label>

												<label for="tcd-color-hair-gray"><span class="tcd-label">Percent Gray:</span>
												<input id="tcd-color-hair-gray" class="small-text" type="number" name="tcd_color_hair_gray" size="3" min="0" max="100" value="<?php echo( $person->color_hair_gray ) ?>" />
												%</label><br/>

												<label for="tcd-color-eye"><span class="tcd-label">Eye Color:</span>
												<select id="tcd-color-eye" name="tcd_color_eye">
													<option value="">Select One</option>
													<?php echo( $eye_options ); ?>
												</select>
												</label><br/>

												<label for="tcd-ethnicity"><span class="tcd-label">Ethnicity:</span>
												<select id="tcd-ethnicity" name="tcd_ethnicity">
													<option value="">Select One</option>
													<?php echo( $race_options ); ?>
												</select>
												</label>
											</fieldset>
											
											<fieldset form="talent-person">

												<label for="tcd-body-height"><span class="tcd-label">Height:</span><input id="tcd-body-height" class="small-text" type="text" name="tcd_body_height" value="<?php echo( $person->body_height ) ?>" /></label>
												<label for="tcd-body-weight"><span class="tcd-label">Weight:</span><input id="tcd-body-weight" class="small-text" type="text" name="tcd_body_weight" value="<?php echo( $person->body_weight ) ?>" /></label><br/><br/>
											</fieldset>
											
											<fieldset form="talent-person">
												<legend><h5>Special Features</h5></legend>
												
												<textarea id="tcd-special-features" class="large-text" name="tcd_special_features" rows="4"><?php echo( $person->special_features ) ?></textarea>
											</fieldset>

										</fieldset><br/>

										<fieldset form="talent-person">
											<legend><h4>Sizing Information</h4></legend>

											<fieldset form="talent-person">
												<label for="tcd-size-shoe"><span class="tcd-label">Shoe Size:</span><input id="tcd-size-shoe" class="small-text" type="text" name="tcd_size_shoe" value="<?php echo( $person->size_shoe ) ?>" /></label>
												<label for="tcd-size-boot" class="tcd-size-mens tcd-size-womens"><span class="tcd-label">Boot Size:</span><input id="tcd-size-boot" class="small-text" type="text" name="tcd_size_boot" value="<?php echo( $person->size_boot ) ?>" /></label><br/>
											</fieldset>

											<fieldset form="talent-person">
												<label for="tcd-size-shirt"><span class="tcd-label">Shirt Size:</span><input id="tcd-size-shirt" class="small-text" type="text" name="tcd_size_shirt" value="<?php echo( $person->size_shirt ) ?>" /></label>
												<label for="tcd-size-shirt-neck" class="tcd-size-mens"><span class="tcd-label">Shirt (Neck):</span><input id="tcd-size-shirt-neck" class="small-text" type="text" name="tcd_size_shirt_neck" value="<?php echo( $person->size_shirt_neck ) ?>" /></label>
												<label for="tcd-size-shirt-sleeve" class="tcd-size-mens"><span class="tcd-label">Shirt (Sleeve):</span><input id="tcd-size-shirt-sleeve" class="small-text" type="text" name="tcd_size_shirt_sleeve" value="<?php echo( $person->size_shirt_sleeve ) ?>" /></label><br/>
												<label for="tcd-size-dress" class="tcd-size-womens"><span class="tcd-label">Dress Size:</span><input id="tcd-size-dress" class="small-text" type="text" name="tcd_size_dress" value="<?php echo( $person->size_dress ) ?>" /><br/></label>
												<label for="tcd-size-jacket" class="tcd-size-mens"><span class="tcd-label">Sport Coat:</span><input id="tcd-size-jacket" class="small-text" type="text" name="tcd_size_jacket" value="<?php echo( $person->size_jacket ) ?>" /><br/></label>
											</fieldset>

											<fieldset form="talent-person">	
												<label for="tcd-size-pant" class="tcd-size-womens tcd-size-childrens"><span class="tcd-label">Pant Size:</span><input id="tcd-size-pant" class="small-text" type="text" name="tcd_size_pant" value="<?php echo( $person->size_pant ) ?>" /></label>
												<label for="tcd-size-waist" class="tcd-size-mens"><span class="tcd-label">Pants (Waist):</span><input id="tcd-size-waist" class="small-text" type="text" name="tcd_size_pant_waist" value="<?php echo( $person->size_pant_waist ) ?>" /></label>
												<label for="tcd-size-pant-lenth" class="tcd-size-mens"><span class="tcd-label">Pants (Inseam):</span><input id="tcd-size-pant-lenth" class="small-text" type="text" name="tcd_size_pant_length" value="<?php echo( $person->size_pant_length ) ?>" /></label>
											</fieldset>
											
											<fieldset form="talent-person">
												<label for="tcd-size-hat"><span class="tcd-label">Hat Size:</span><input id="tcd-size-hat" class="small-text" type="text" name="tcd_size_hat" value="<?php echo( $person->size_hat ) ?>" /></label>
											</fieldset>
										
										</fieldset><br/>

										<fieldset form="talent-person">
											<legend><h4>Skills/Experience</h4></legend>
											<fieldset form="talent-person">
												<legend><h5>Languages</h5></legend>

												<label for="tcd-skill-language">Spoken:<input id="tcd-skill-language" class="large-text" type="text" name="tcd_skill_language" value="<?php echo( $person->skill_language ) ?>" /></label><br/>
												<label for="tcd-skill-accent">Accents:<input id="tcd-skill-accent" class="large-text" type="text" name="tcd_skill_accent" value="<?php echo( $person->skill_accent ) ?>" /></label><br/>
											</fieldset>

											<fieldset form="talent-person">
												<legend><h5>Activities</h5></legend>

												<label for="tcd-size-shirt">Sports:<input id="tcd-skill-sports" class="large-text" type="text" name="tcd_skill_sports" value="<?php echo( $person->skill_sports ) ?>" /></label><br/>
												<label for="tcd-skill-hobby">Hobbies:<input id="tcd-skill-hobby" class="large-text" type="text" name="tcd_skill_hobby" value="<?php echo( $person->skill_hobby ) ?>" /></label><br/>
											</fieldset>
											
											<fieldset form="talent-person">
												<legend><h5>Additional Skills &amp; Experience</h5></legend>

												<label for="tcd-skills" style="display: inline-block; width: 100%;">Skills:<br/>
												<textarea id="tcd-skills" class="large-text" name="tcd_skills" rows="4"><?php echo( $person->skills ) ?></textarea>
												</label><br/>

												<label for="tcd-experience" style="display: inline-block; width: 100%;">Experience:<br/>
												<textarea id="tcd-experience" class="large-text" name="tcd_experience" rows="4"><?php echo( $person->experience ) ?></textarea>
												</label>
											</fieldset>

										</fieldset><br/>
										

										<fieldset form="talent-person">
											<legend><h4>Notes</h4></legend>

												<textarea id="tcd-notes" class="large-text" name="tcd_notes" rows="6"><?php echo( $person->notes ) ?></textarea>

										</fieldset><br/>

									</div><!-- /inside -->
								</div><!-- /tcd-person-settings -->


							
							
								<div id="tcd-person-additional-images" class="meta-box-sortables ui-sortable">
								
									<div id="tcd-person-images" class="postbox" style="display: block;">
										<div class="handlediv" title="Click to toggle"><br /></div>
										<h3 class='hndle'><span>Additional Images</span></h3>
										<div class="inside">
										
											<fieldset class="tcd-meta-section" form="talent-person"> 
												<div class="tcd-control-group tcd-main-set" style="width: 99%; margin-bottom: 5px; padding: 5px; border: 1px solid #bbb; border-radius: 4px;">
													<div id="tcd-auxillary-container">
														<?php echo($additional_images); ?>
													</div>	
													<span id="upload-button" class="button-primary button-large" style="position: relative;">
														Upload Image
														<span id="tcd-auxillary-upload-input"><input type="file" id="auxillary-upload" class="tcd-upload-button" name="auxillary_upload" size="20" /></span>
													</span>
												</div><!-- /tcd-control-group -->
											</fieldset><!-- /tcd-meta-section -->
										
										</div><!-- /inside -->
									</div><!-- /tcd-person-options -->
									
								</div><!-- /person-assignment -->
							</div><!-- /normal-sortables -->
						</div><!-- /postbox-container-2 -->
						
					</div><!-- /post-body -->
					<br class="clear" />
				</div><!-- /poststuff -->
			</form>
		</div><!-- /wrap -->
		
		<?php
		// End Form Output
	}

	public static function file_url_exists( $url )
	{
		$url = str_replace("http://", "", $url);
		if (strstr($url, "/")) {
			$url = explode("/", $url, 2);
			$url[1] = "/".$url[1];
		} else {
			$url = array($url, "/");
		}

		$fh = fsockopen($url[0], 80);
		if ($fh) {
			fputs($fh,"GET ".$url[1]." HTTP/1.1\nHost:".$url[0]."\n\n");
			if (fread($fh, 22) == "HTTP/1.1 404 Not Found") { return FALSE; }
			else { return TRUE;    }

		} else { return FALSE;}
	}


} // end class
} // end if/exists
?>