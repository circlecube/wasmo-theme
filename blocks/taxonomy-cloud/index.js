import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, RangeControl, ToggleControl } from '@wordpress/components';

// Import styles
import './editor.scss';
import './style.scss';

// Taxonomy configuration
const taxonomyConfig = {
    shelf: {
        title: 'Mormon shelf issues:',
        icon: '📚',
    },
    spectrum: {
        title: 'Mormon Spectrum:',
        icon: '🌈',
    },
    question: {
        title: 'Questions about the Mormon Church:',
        icon: '❓',
    },
};

registerBlockType( 'wasmo/taxonomy-cloud', {
    edit: ( { attributes, setAttributes } ) => {
        const { taxonomy, title, headingLevel, showIcon, orderBy, order, hideEmpty } = attributes;
        const blockProps = useBlockProps();

        const config = taxonomyConfig[ taxonomy ] || taxonomyConfig.shelf;
        const displayTitle = title || config.title;

        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={ __( 'Taxonomy Settings', 'wasmo-theme' ) } initialOpen={ true }>
                        <SelectControl
                            label={ __( 'Taxonomy', 'wasmo-theme' ) }
                            value={ taxonomy }
                            options={ [
                                { label: __( 'Shelf Issues', 'wasmo-theme' ), value: 'shelf' },
                                { label: __( 'Mormon Spectrum', 'wasmo-theme' ), value: 'spectrum' },
                                { label: __( 'Questions', 'wasmo-theme' ), value: 'question' },
                            ] }
                            onChange={ ( value ) => setAttributes( { taxonomy: value } ) }
                        />

                        <TextControl
                            label={ __( 'Custom Title', 'wasmo-theme' ) }
                            value={ title }
                            onChange={ ( value ) => setAttributes( { title: value } ) }
                            placeholder={ config.title }
                            help={ __( 'Leave empty to use default title.', 'wasmo-theme' ) }
                        />

                        <RangeControl
                            label={ __( 'Heading Level', 'wasmo-theme' ) }
                            value={ headingLevel }
                            onChange={ ( value ) => setAttributes( { headingLevel: value } ) }
                            min={ 1 }
                            max={ 6 }
                        />

                        <ToggleControl
                            label={ __( 'Show Icon', 'wasmo-theme' ) }
                            checked={ showIcon }
                            onChange={ ( value ) => setAttributes( { showIcon: value } ) }
                        />
                    </PanelBody>

                    <PanelBody title={ __( 'Sort Options', 'wasmo-theme' ) } initialOpen={ false }>
                        <SelectControl
                            label={ __( 'Order By', 'wasmo-theme' ) }
                            value={ orderBy }
                            options={ [
                                { label: __( 'Name', 'wasmo-theme' ), value: 'name' },
                                { label: __( 'Count', 'wasmo-theme' ), value: 'count' },
                                { label: __( 'ID', 'wasmo-theme' ), value: 'id' },
                                { label: __( 'Slug', 'wasmo-theme' ), value: 'slug' },
                            ] }
                            onChange={ ( value ) => setAttributes( { orderBy: value } ) }
                        />

                        <SelectControl
                            label={ __( 'Order', 'wasmo-theme' ) }
                            value={ order }
                            options={ [
                                { label: __( 'Ascending (A-Z)', 'wasmo-theme' ), value: 'ASC' },
                                { label: __( 'Descending (Z-A)', 'wasmo-theme' ), value: 'DESC' },
                            ] }
                            onChange={ ( value ) => setAttributes( { order: value } ) }
                        />

                        <ToggleControl
                            label={ __( 'Hide Empty Terms', 'wasmo-theme' ) }
                            checked={ hideEmpty }
                            onChange={ ( value ) => setAttributes( { hideEmpty: value } ) }
                            help={ __( 'Only show terms that have content.', 'wasmo-theme' ) }
                        />
                    </PanelBody>
                </InspectorControls>

                <div className={ `taxonomy-cloud-preview taxonomy-${ taxonomy }` }>
                    <div className="preview-header">
                        { showIcon && <span className="preview-icon">{ config.icon }</span> }
                        <span className="preview-title">{ displayTitle }</span>
                        <span className="preview-heading-level">(H{ headingLevel })</span>
                    </div>
                    <div className="preview-tags">
                        <span className="tag-placeholder"></span>
                        <span className="tag-placeholder"></span>
                        <span className="tag-placeholder"></span>
                        <span className="tag-placeholder"></span>
                        <span className="tag-placeholder"></span>
                        <span className="tag-placeholder"></span>
                    </div>
                    <p className="preview-meta">
                        { __( 'Taxonomy:', 'wasmo-theme' ) } <strong>{ taxonomy }</strong> · 
                        { __( 'Order:', 'wasmo-theme' ) } { orderBy } ({ order })
                    </p>
                </div>
            </div>
        );
    },

    save: () => {
        // Server-side rendering
        return null;
    }
} );
