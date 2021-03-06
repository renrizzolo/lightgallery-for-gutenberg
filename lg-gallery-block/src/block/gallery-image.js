/**
 * External Dependencies
 */
import classnames from "classnames";

/**
 * WordPress Dependencies
 */

// import { Component, Fragment } from '@wordpress/element';
// import { IconButton, Spinner } from '@wordpress/components';
// import { __ } from '@wordpress/i18n';
// import { BACKSPACE, DELETE } from '@wordpress/keycodes';
// import { withSelect } from '@wordpress/data';
// import { RichText } from '@wordpress/editor';
// import { isBlobURL } from '@wordpress/blob';

const { Component, Fragment } = wp.element;
const { IconButton, Spinner, Modal } = wp.components;
const { __ } = wp.i18n;
const { BACKSPACE, DELETE } = wp.keycodes;
const { withSelect } = wp.data;
const { RichText } = wp.editor;
const { isBlobURL } = wp.blob;

class GalleryImage extends Component {
	constructor() {
		super(...arguments);

		this.onImageClick = this.onImageClick.bind(this);
		this.onSelectCaption = this.onSelectCaption.bind(this);
		this.onKeyDown = this.onKeyDown.bind(this);
		this.bindContainer = this.bindContainer.bind(this);

		this.state = {
			captionSelected: false,
		};
	}

	bindContainer(ref) {
		this.container = ref;
	}

	onSelectCaption() {
		if (!this.state.captionSelected) {
			this.setState({
				captionSelected: true,
			});
		}

		if (!this.props.isSelected) {
			this.props.onSelect();
		}
	}

	onImageClick() {
		if (!this.props.isSelected) {
			this.props.onSelect();
		}

		if (this.state.captionSelected) {
			this.setState({
				captionSelected: false,
			});
		}
	}

	onKeyDown(event) {
		if (
			this.container === document.activeElement &&
			this.props.isSelected &&
			[BACKSPACE, DELETE].indexOf(event.keyCode) !== -1
		) {
			event.stopPropagation();
			event.preventDefault();
			this.props.onRemove();
		}
	}

	componentDidUpdate(prevProps) {
		const { isSelected, image, url } = this.props;
		console.log("didupdate", image);

		if (image && !url) {
			this.props.setAttributes({
				url: image.source_url,
				alt: image.alt_text,
			});
		}

		// unselect the caption so when the user selects other image and comeback
		// the caption is not immediately selected
		if (this.state.captionSelected && !isSelected && prevProps.isSelected) {
			this.setState({
				captionSelected: false,
			});
		}
	}

	render() {
		const {
			url,
			alt,
			id,
			link,
			isSelected,
			caption,
			onRemove,
			setAttributes,
			classes,
			"aria-label": ariaLabel,
		} = this.props;
		console.log("url", url);

		const img = url ? (
			// Disable reason: Image itself is not meant to be interactive, but should
			// direct image selection and unfocus caption fields.
			/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
			<Fragment>
				<img
					src={url}
					alt={alt}
					data-id={id}
					onClick={this.onImageClick}
					tabIndex="0"
					onKeyDown={this.onImageClick}
					aria-label={ariaLabel}
				/>
				{isBlobURL(url) && <Spinner />}
			</Fragment>
		) : (
			/* eslint-enable jsx-a11y/no-noninteractive-element-interactions */
			<p>Image unavailable</p>
		);

		const className = classnames(
			{
				"is-selected": isSelected,
				"is-transient": isBlobURL(url),
			},
			classes
		);

		// Disable reason: Each block can be selected by clicking on it and we should keep the same saved markup
		/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/onclick-has-role, jsx-a11y/click-events-have-key-events */
		return (
			<figure
				className={className}
				tabIndex="-1"
				onKeyDown={this.onKeyDown}
				ref={this.bindContainer}
			>
				{isSelected && (
					<div className="core-blocks-gallery-item__inline-menu">
						<IconButton
							icon="no-alt"
							onClick={onRemove}
							className="blocks-gallery-item__remove"
							label={__("Remove Image")}
						/>
					</div>
				)}
				{img}
				{(caption && caption.length > 0) || isSelected ? (
					<RichText
						// format="string"
						value={caption}
						tagName="figcaption"
						placeholder={__("Write caption…")}
						isSelected={this.state.captionSelected}
						onChange={(newCaption) => setAttributes({ caption: newCaption })}
						unstableOnFocus={this.onSelectCaption}
						inlineToolbar
					/>
				) : null}

				{/* { this.state.isOpen ? (
					<Modal
						title="Edit caption"
						onRequestClose={() => this.setState({isOpen: false})}>
						<div style={{textAlign: "center"}}>
							<img src={url} className="lg-blocks-modal-image"/>
							<RichText
								tagName="figcaption"
								placeholder={ __( 'Write caption…' ) }
								value={ caption }
								isSelected={this.state.isOpen }
								onChange={ ( newCaption ) => setAttributes( { caption: newCaption } ) }
								// unstableOnFocus={ this.onSelectCaption }
								inlineToolbar
							/>
						</div>
					</Modal> 
				) : null } */}
			</figure>
		);
		/* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/onclick-has-role, jsx-a11y/click-events-have-key-events */
	}
}

export default withSelect((select, ownProps) => {
	const { getMedia } = select("core");
	const { id } = ownProps;

	return {
		image: id ? getMedia(id) : null,
	};
})(GalleryImage);
