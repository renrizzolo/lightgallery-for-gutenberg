/**
 * External dependencies
 */
import { filter, every } from 'lodash';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { createBlock } = wp.blocks;
const { RichText, mediaUpload } = wp.editor;
const { createBlobURL } = wp.blob;
const { registerBlockType } = wp.blocks;

/**
 * Internal dependencies
 */
import './style.scss';
import { default as edit, defaultColumnsNumber } from './edit';

const blockAttributes = {
	// images: {
	// 	type: 'array',
	// 	default: [],
	// 	source: 'query',
	// 	selector: 'div.wp-block-lgng-gallery',
	// 	query: {
	// 		url: {
	// 			source: 'attribute',
	// 			selector: 'img',
	// 			attribute: 'src',
	// 		},
	// 		link: {
	// 			source: 'attribute',
	// 			selector: 'img',
	// 			attribute: 'data-link',
	// 		},
	// 		alt: {
	// 			source: 'attribute',
	// 			selector: 'img',
	// 			attribute: 'alt',
	// 			default: '',
	// 		},
	// 		id: {
	// 			source: 'attribute',
	// 			selector: 'img',
	// 			attribute: 'data-id',
	// 		},
	// 		caption: {
	// 			type: 'array',
	// 			source: 'children',
	// 			selector: 'figcaption',
	// 		},
	// 		thumbnail: {
	// 			source: 'attribute',
	// 			selector: 'img',
	// 			attribute: 'data-thumb',
	// 		}
	// 	},
	// },

	columns: {
		type: 'number',
		default: 8,
	},
	imageCrop: {
		type: 'boolean',
		default: true,
	},
	lightslider: {
		type: 'boolean',
		default: true,
	},
	linkTo: {
		type: 'string',
		default: 'none',
	},
	// mode: {
	// 	type: 'string',
	// 	default: 'lg-slide',
	// },
	lightSliderOptions: {
		type: 'string',
		default: '',

	},
	lightGalleryOptions: {
		type: 'string',
		default: '',
	},
};

const name = 'lgng/gallery';

const settings = {
	title: __( 'Lightgallery Gallery' ),
	description: __( 'Lightgallery/lightslider gallery.' ),
	icon: 'format-gallery',
	category: 'common',
	keywords: [ __( 'images' ), __( 'photos' ) ],
	// attributes: blockAttributes,
	supports: {
		align: ['center', 'wide', 'full' ],
		anchor: true,
	},

	transforms: {
		from: [
			{
				type: 'block',
				isMultiBlock: true,
				blocks: [ 'core/image' ],
				transform: ( attributes ) => {
					const validImages = filter( attributes, ( { id, url } ) => id && url );
					if ( validImages.length > 0 ) {
						console.log(validImages, 'images to transform to lgng block')

						return createBlock( 'lgng/gallery', {
							images: JSON.stringify(validImages.map( ( { id, url, alt, caption } ) => ( { id, url, alt, caption } ) )),
							columns: validImages.length,
							ls_mode: 'lg-slide',
							lg_mode: 'Slide',
							align: 'center',
							lightslider: true,
							lightgallery: true,
						} );
					}
					return createBlock( 'lgng/gallery' );
				},
			},
			{
				type: 'shortcode',
				tag: 'lg_gallery',
				attributes: {
					images: {
						type: 'array',
						shortcode: ( { named: { ids } } ) => {
							if ( ! ids ) {
								return [];
							}

							return ids.split( ',' ).map( ( id ) => ( {
								id: parseInt( id, 10 ),
							} ) );
						},
					},
					columns: {
						type: 'number',
						shortcode: ( { named: { columns = '3' } } ) => {
							return parseInt( columns, 10 );
						},
					},
					// linkTo: {
					// 	type: 'string',
					// 	shortcode: ( { named: { link = 'attachment' } } ) => {
					// 		return link === 'file' ? 'media' : link;
					// 	},
					// },
				},
			},
			{
				// When created by drag and dropping multiple files on an insertion point
				type: 'files',
				isMatch( files ) {
					return files.length !== 1 && every( files, ( file ) => file.type.indexOf( 'image/' ) === 0 );
				},
				transform( files, onChange ) {
					const block = createBlock( 'lgng/gallery', {
						images: files.map( ( file ) => ( { url: createBlobURL( file ) } ) ),
					} );
					mediaUpload( {
						filesList: files,
						onFileChange: ( images ) => onChange( block.clientId, { images } ),
						allowedType: 'image',
					} );
					return block;
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/image' ],
				transform: ( { images } ) => {
					const toTransform = JSON.parse(images);
					if (toTransform.length > 0 ) {
						console.log(toTransform, 'images to transform to image block')
						return toTransform.map( ( { id, url, alt, caption } ) => createBlock( 'core/image', { id, url, alt, caption } ) );
					}
					return createBlock( 'core/image' );
				},
			},
		],
	},

	edit,

	save( { attributes } ) {
		 return null;
		// const blockId = new Date().now;
		// const {
		// 	timestamp,
		// 	images, 
		// 	columns = defaultColumnsNumber( attributes ), 
		// 	imageCrop, 
		// 	linkTo, 
		// 	lightslider, 
		// 	mode, 
		// 	lightSliderOptions, 
		// 	lightGalleryOptions } = attributes;
		// let colClass;
		// switch ( columns ) {
		// 	case 6:
		// 		colClass = 'lgng-6-cols';
		// 		break;
		// 	case 5:
		// 		colClass = 'lgng-5-cols';
		// 		break;
		// 	case 4:
		// 		colClass = 'lgng-4-cols';
		// 		break;
		// 	case 3:
		// 		colClass = 'lgng-3-cols';
		// 		break;
		// 	case 2:
		// 		colClass = 'lgng-2-cols';
		// 		break;
		// 	default:
		// 		colClass = 'lgng-4-cols';
		// 		break;
		// }

		// return (
		// 	<div>
		// 	{
		// 			console.log('cols', colClass, columns),

		// 		jQuery && lightslider ?
		// 			<script>
		// 				{`
		// 					jQuery(document).ready(function($){
		// 					$('.lightgallery#lg-block--${blockId}').lightGallery({
		// 						thumbnail: true,
		// 						selector: '.lg-open',
		// 						download: false,
		// 						showAfterLoad: true,
		// 						subHtmlSelectorRelative: true,
		// 						hideBarsDelay: 2000,
		// 						exThumbImage: 'data-exthumbimage',
		// 						mode: '${mode}',
		// 						${lightGalleryOptions}
		// 					});
		// 						$('.lightgallery#lg-block--${blockId}').lightSlider({
		// 							gallery: true,
		// 							item: 1,
		// 							loop: true,
		// 							slideMargin: 0,
		// 							thumbItem: '${columns}',
		// 							gallery: true,
		// 							mode: 'slide',
		// 							${lightSliderOptions}
		// 						});
		// 					});
		// 				`}
		// 			</script>
		// 			:
		// 			<script>
		// 				{`
		// 					jQuery(document).ready(function($){
		// 						$('.lightgallery#lg-block--${blockId}').lightGallery({
		// 							thumbnail: true,
		// 							selector: '.item',
		// 							download: false,
		// 							showAfterLoad: true,
		// 							subHtmlSelectorRelative: true,
		// 							hideBarsDelay: 2000,
		// 							exThumbImage: 'data-exthumbimage',
		// 							mode: '${mode}',
		// 							${lightGalleryOptions}
		// 						})
		// 					});
		// 				`}
		// 			</script>
		// 	}
		// 	<div data-block={blockId} id={`lg-block--${blockId}`} className={`lightgallery lgng-row lightSlider lsGrab lSSlide  ${ imageCrop ? 'is-cropped' : '' }` }>
			
		// 		{ images.map( ( image ) => {
		// 			let href;
		// 			console.log('lg-image:', image);
					
		// 			switch ( linkTo ) {
		// 				case 'media':
		// 					href = image.url;
		// 					break;
		// 				case 'attachment':
		// 					href = image.link;
		// 					break;
		// 			}
		// 			const thumb = image.thumbnail ? image.thumbnail : image.sizes.thumbnail.url;
		// 			const img = <img src={ image.url } alt={ image.alt } data-id={ image.id } data-thumb={thumb} data-link={ image.link } className={ image.id ? `wp-image-${ image.id }` : null } />;

		// 		return (
		// 			lightslider ? 
		// 				<figure key={image.id || image.url} className="ls-item" data-thumb={thumb}>
		// 						{ href ? <a href={ href }>{ img }</a> : img }
		// 					<span class="item lg-open lg-fullscreen lg-icon" data-exthumbimage={thumb} data-sub-html={`${image.caption}`} data-src={image.url}>
		//          	</span>
		// 				</figure>
		// 				:
		// 				<a data-sub-html={`${image.caption}`} data-exthumbimage={thumb} className={`item ${colClass}`} href={image.url}>
		// 					<img class="" src={thumb}/>
		// 				</a>
		// 			);
		// 		} ) }
		// 	</div>
		// 	</div>
		// );
	},

};

/* register the block */

registerBlockType( name, settings );