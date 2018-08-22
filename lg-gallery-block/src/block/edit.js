/**
 * External Dependencies
 */
import { filter, pick } from 'lodash';

/**
 * WordPress dependencies
 */
const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const {
	IconButton,
	Button,
	DropZone,
	FormFileUpload,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	TextareaControl,
	Toolbar,
	withNotices,
} = wp.components;
const {
	BlockControls,
	MediaUpload,
	MediaPlaceholder,
	InspectorControls,
	mediaUpload,
} = wp.editor;

/**
 * Internal dependencies
 */
import './editor.scss';
import GalleryImage from './gallery-image';

const MAX_THUMBS = 12;
const MAX_COLUMNS = 6;
const linkOptions = [
	{ value: 'attachment', label: __( 'Attachment Page' ) },
	{ value: 'media', label: __( 'Media File' ) },
	{ value: 'none', label: __( 'None' ) },
];

const lgModeOptions = [
	{ value: 'lg-slide', label: __( 'Slide' ) },
	{ value: 'lg-fade', label: __( 'Fade' ) },
	{ value: 'lg-zoom-in', label: __( 'Zoom In' ) },
	{ value: 'lg-zoom-in-big', label: __( 'Zoom In Big' ) },
	{ value: 'lg-zoom-out', label: __( 'Zoom Out' ) },
	{ value: 'lg-zoom-out-big', label: __( 'Zoom Out Big' ) },
	{ value: 'lg-zoom-out-in', label: __( 'Zoom Out In' ) },
	{ value: 'lg-zoom-in-out', label: __( 'Zoom In Out' ) },
	{ value: 'lg-soft-zoom', label: __( 'Soft Zoom' ) },
	{ value: 'lg-scale-up', label: __( 'Scale Up' ) },
	{ value: 'lg-slide-circular', label: __( 'Slide Circular' ) },
	{ value: 'lg-slide-circular-vertical', label: __( 'Slide Circular Vertical' ) },
	{ value: 'lg-slide-vertical', label: __( 'Slide Vertical' ) },
	{ value: 'lg-slide-vertical-growth', label: __( 'Slide Vertical Growth' ) },
	{ value: 'lg-slide-skew-only', label: __( 'Slide Skew Only' ) },
	{ value: 'lg-slide-skew-only-rev', label: __( 'Slide Skew Only Reverse' ) },
	{ value: 'lg-slide-skew-only-y', label: __( 'Slide Skew Only Y' ) },
	{ value: 'lg-slide-skew-only-y-rev', label: __( 'Slide Skew Only Y Reverse' ) },
	{ value: 'lg-slide-skew', label: __( 'Slide Skew' ) },
	{ value: 'lg-slide-skew-rev', label: __( 'Slide Skew Reverse' ) },
	{ value: 'lg-slide-skew-cross', label: __( 'Slide Skew Cross' ) },
	{ value: 'lg-slide-skew-cross-rev', label: __( 'Slide Skew Cross Reverse' ) },
	{ value: 'lg-slide-skew-ver', label: __( 'Slide Skew Vertical' ) },
	{ value: 'lg-slide-skew-ver-rev', label: __( 'Slide Skew Vertical Reverse' ) },
	{ value: 'lg-slide-skew-ver-cross', label: __( 'Slide Skew Vertical Cross' ) },
	{ value: 'lg-slide-skew-ver-cross-rev', label: __( 'Slide Skew Vertical Cross Reverse' ) },
	{ value: 'lg-lollipop', label: __( 'Lollipop' ) },
	{ value: 'lg-lollipop-rev', label: __( 'Lollipop Reverse' ) },
	{ value: 'lg-rotate', label: __( 'Rotate' ) },
	{ value: 'lg-rotate-rev', label: __( 'Rotate Reverse' ) },
	{ value: 'lg-tube', label: __( 'Tube' ) },
];
const lsModeOptions = [
	{ value: 'slide', label: __( 'Slide' ) },
	// lightslider fade is a bit broken
	{/* { value: 'fade', label: __( 'Fade' ) }, */}
];

export function defaultColumnsNumber( attributes ) {
	return Math.min( 3, JSON.parse(attributes.images).length );
}

class GalleryEdit extends Component {
	constructor() {
		super( ...arguments );

		this.onSelectImage = this.onSelectImage.bind( this );
		this.onSelectImages = this.onSelectImages.bind( this );
		this.setLinkTo = this.setLinkTo.bind( this );
		this.setLgMode = this.setLgMode.bind(this);
		this.setLsMode = this.setLsMode.bind(this);

		this.setColumnsNumber = this.setColumnsNumber.bind( this );
		this.toggleLightslider = this.toggleLightslider.bind( this );
		this.toggleLightgallery = this.toggleLightgallery.bind( this );

		this.onRemoveImage = this.onRemoveImage.bind( this );
		this.setImageAttributes = this.setImageAttributes.bind( this );
		this.addFiles = this.addFiles.bind( this );
		this.uploadFromFiles = this.uploadFromFiles.bind( this );
		this.setLightSliderOptions = this.setLightSliderOptions.bind(this);
		this.setLightGalleryOptions = this.setLightGalleryOptions.bind(this);

		this.state = {
			selectedImage: null,
		};
	}

	onSelectImage( index ) {
		return () => {
			if ( this.state.selectedImage !== index ) {
				this.setState( {
					selectedImage: index,
				} );
			}
		};
	}

	onRemoveImage( index ) {
		return () => {
			const images = filter( JSON.parse(this.props.attributes.images), ( img, i ) => index !== i );
			const { columns } = this.props.attributes;
			this.setState( { selectedImage: null } );
			this.props.setAttributes( {
				images: JSON.stringify(images),
				columns: columns ? Math.min( images.length, columns ) : columns,
			} );
		};
	}

	onSelectImages( images ) {
		console.log('onsel', images);
		// const existingImages = JSON.parse(this.props.attributes.images);
		const newImages = [
			// ...existingImages,
			...images.map( (image) => pick(image, ['alt', 'caption', 'id', 'link', 'url',]) )

		];
		console.log(newImages);
		
		this.props.setAttributes({
			images: JSON.stringify(newImages)
		});
	}

	setLinkTo( value ) {
		this.props.setAttributes( { linkTo: value } );
	}
	setLgMode(value) {
		this.props.setAttributes({ lg_mode: value });
	}
	setLsMode(value) {
		this.props.setAttributes({ ls_mode: value });
	}
	setLightSliderOptions( value ) {
		this.props.setAttributes({ lightSliderOptions: value });
	}
	setLightGalleryOptions(value) {
		this.props.setAttributes({ lightGalleryOptions: value });
	}
	setColumnsNumber( value ) {
		this.props.setAttributes( { columns: value } );
	}




	toggleLightslider() {
		const { attributes: { columns, lightslider, lightgallery } } = this.props;
		const max_cols = lightslider ? MAX_COLUMNS : MAX_THUMBS;
		this.props.setAttributes({ 
			lightslider: !lightslider,
			lightgallery: true,
			columns: columns > max_cols ? max_cols : columns  
		});
	}
	toggleLightgallery() {
		const { attributes: { lightslider, lightgallery } } = this.props;
		// can't disable gallery if slider isn't enabled
		if (!lightslider ) return;

		this.props.setAttributes({ 
			lightgallery: !lightgallery, 
		});
	}
	getLightgalleryHelp(checked) {
		return checked ? __('Slider can be expanded to a fullscreen gallery.') : __('fullscreen gallery disabled.');
	}

	getLightsliderHelp(checked) {
		return checked ? __('Images are shown in a slider.') : __('Images are shown in a grid.');
	}

	setImageAttributes( index, attributes ) {
		const { attributes: { images }, setAttributes } = this.props;
		const parsedImages = JSON.parse(images);
		if ( ! parsedImages[ index ] ){
			return;
		}
		setAttributes( {
			images: JSON.stringify([
				...parsedImages.slice( 0, index ),
				{
					...parsedImages[ index ],
					...attributes,
				},
				...parsedImages.slice( index + 1 ),
			]),
		} );
	}

	uploadFromFiles( event ) {
		this.addFiles( event.target.files );
	}

	addFiles( files ) {
		const currentImages = JSON.parse(this.props.attributes.images) || [];
		const { noticeOperations, setAttributes } = this.props;
		mediaUpload( {
			allowedType: 'image',
			filesList: files,
			onFileChange: ( images ) => {
				setAttributes( {
					images: JSON.stringify(currentImages.concat( images )),
				} );
			},
			onError: noticeOperations.createErrorNotice,
		} );
	}

	componentDidUpdate( prevProps ) {
		// Deselect images when deselecting the block
		if ( ! this.props.isSelected && prevProps.isSelected ) {
			this.setState( {
				selectedImage: null,
				captionSelected: false,
			} );
		}
	}

	render() {
		const { attributes, isSelected, className, noticeOperations, noticeUI } = this.props;
		const { 
			images, 
			columns = defaultColumnsNumber( attributes ), 
			align, 
			linkTo, 
			lightslider,
			lightgallery,
			ls_mode,
			lg_mode, 
			lightSliderOptions,
			lightGalleryOptions
		} = attributes;
		console.log(images);
		const dropZone = (
			<DropZone
				onFilesDrop={ this.addFiles }
			/>
		);
		const parsedImages = JSON.parse(images);
		const controls = (
			<BlockControls>
				{ !! parsedImages.length && (
					<Toolbar>
						<MediaUpload
							onSelect={ this.onSelectImages }
							type="image"
							multiple
							gallery
							value={ parsedImages.map( ( img ) => img.id ) }
							render={ ( { open } ) => (
								<IconButton
									className="components-toolbar__control"
									label={ __( 'Edit Gallery' ) }
									icon="edit"
									onClick={ open }
								/>
							) }
						/>
					</Toolbar>
				) }
			</BlockControls>
		);

		if ( parsedImages.length === 0 ) {
			return (
				<Fragment>
					{ controls }
					<MediaPlaceholder
						icon="format-gallery"
						className={ className }
						labels={ {
							title: __( 'Gallery' ),
							name: __( 'images' ),
						} }
						onSelect={ this.onSelectImages }
						accept="image/*"
						type="image"
						multiple
						notices={ noticeUI }
						onError={ noticeOperations.createErrorNotice }
					/>
				</Fragment>
			);
		}

		return (
			<Fragment>
				{ controls }
				<InspectorControls>
					<PanelBody title={ __( 'Gallery Settings' ) }>
						<ToggleControl
							label={__('Show in lightslider')}
							checked={!!lightslider}
							onChange={this.toggleLightslider}
							help={this.getLightsliderHelp}
						/>
						{lightslider && 
							<ToggleControl
								label={__('Enable lightgallery')}
								checked={!!lightgallery}
								onChange={this.toggleLightgallery}
								help={this.getLightgalleryHelp}
							/>
						}
						{ parsedImages.length > 1 && <RangeControl
							label={ __( lightslider ? 'amount of thumbs to show under lightslider' : 'grid columns' ) }
							value={ columns }
							onChange={ this.setColumnsNumber }
							min={ lightslider ? 0 : 1 }
							max={ lightslider ? MAX_THUMBS : MAX_COLUMNS }
						/> }
						<SelectControl
							label={__('Slider slide mode')}
							value={ls_mode}
							onChange={this.setLsMode}
							options={lsModeOptions}
						/>
						<SelectControl
							label={__('Gallery slide mode')}
							value={lg_mode}
							onChange={this.setLgMode}
							options={lgModeOptions}
						/>

						<TextareaControl
							label={__('Lightslider additional options')}
							value={ lightSliderOptions }
							help="Enter comma separated key/value pairs (in quotes) e.g 'hideBarsDelay': 10000 sachinchoolur.github.io/lightslider/settings.html"
							onChange={ this.setLightSliderOptions }
						/>
						<TextareaControl
							label={__('Lightgallery additional options')}
							value={lightGalleryOptions}
							help="Enter comma separated key/value pairs (in quotes) sachinchoolur.github.io/lightGallery/docs/api.html"
							onChange={this.setLightGalleryOptions}
						/>
					</PanelBody>
				</InspectorControls>
				{ noticeUI }
				<div className={ `lg-blocks-gallery-item ${ className } align${ align } ` }>
					{ dropZone }
					<div class="lg-blocks-gallery-container">
						{ parsedImages.map( ( img, index ) => (
							<GalleryImage
								key={img.id || img.url}
								url={ img.url }
								alt={ img.alt }
								id={ img.id }
								classes={(index > 0 || !lightslider) && `lgng-${columns}-cols is-thumb`}
								isSelected={ isSelected && this.state.selectedImage === index }
								onRemove={ this.onRemoveImage( index ) }
								onSelect={ this.onSelectImage( index ) }
								setAttributes={ ( attrs ) => this.setImageAttributes( index, attrs ) }
								caption={ img.caption }
							/>
						) ) }
					</div>
					{ isSelected &&
						<div>
						{/* 	<div className="lg-blocks-gallery-item has-add-item-button">
								<FormFileUpload
									multiple
									isLarge
									className="core-blocks-gallery-add-item-button"
									onChange={ this.uploadFromFiles }
									accept="image/*"
									icon="insert"
								>
									{ __( 'Upload an image' ) }
								</FormFileUpload>
							</div> */}
						<MediaUpload
							onSelect={this.onSelectImages}
							type="image"
							multiple
							gallery
							value={parsedImages.map((img) => img.id)}
							render={({ open }) => (
								<div className="lg-blocks-edit-gallery">
									<Button	onClick={open}>{__('Edit Gallery')}</Button>
								</div>
							)}
						/>
						{/* 	<MediaPlaceholder
								icon="format-gallery"
								className={className}
								labels={{
									title: __('Gallery'),
									name: __('images'),
								}}
								onSelect={this.onSelectImages}
								accept="image/*"
								type="image"
								multiple
								notices={noticeUI}
								onError={noticeOperations.createErrorNotice}
							/> */}
						</div>
					}
				</div>
			</Fragment>
		);
	}
}

export default withNotices( GalleryEdit );
