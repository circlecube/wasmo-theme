import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

// Import styles
import './editor.scss';
import './style.scss';

registerBlockType( 'wasmo/user-directory', {
    edit: ( { attributes, setAttributes } ) => {
        const { context, maxProfiles, showLoadMore, showButtons, taxonomyFilter, termId, requireImage } = attributes;
        const blockProps = useBlockProps();

        // Fetch users for preview
        const users = useSelect( ( select ) => {
            return select( 'core' ).getUsers( {
                per_page: Math.min( maxProfiles, 12 ),
                orderby: 'registered',
                order: 'desc',
            } );
        }, [ maxProfiles ] );

        const displayCount = Math.min( maxProfiles, 12 );

        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={ __( 'Display Settings', 'wasmo-theme' ) } initialOpen={ true }>
                        <SelectControl
                            label={ __( 'Context', 'wasmo-theme' ) }
                            value={ context }
                            options={ [
                                { label: __( 'Widget (Compact)', 'wasmo-theme' ), value: 'widget' },
                                { label: __( 'Full Directory', 'wasmo-theme' ), value: 'full' },
                            ] }
                            onChange={ ( value ) => setAttributes( { context: value } ) }
                            help={ __( 'Widget shows fewer profiles, Full shows paginated directory.', 'wasmo-theme' ) }
                        />

                        <RangeControl
                            label={ __( 'Maximum Profiles', 'wasmo-theme' ) }
                            value={ maxProfiles }
                            onChange={ ( value ) => setAttributes( { maxProfiles: value } ) }
                            min={ 3 }
                            max={ 99 }
                            help={ __( 'Number of profiles to display.', 'wasmo-theme' ) }
                        />

                        <ToggleControl
                            label={ __( 'Show Load More Button', 'wasmo-theme' ) }
                            checked={ showLoadMore }
                            onChange={ ( value ) => setAttributes( { showLoadMore: value } ) }
                            help={ __( 'Enable lazy loading with a "Load More" button.', 'wasmo-theme' ) }
                        />

                        <ToggleControl
                            label={ __( 'Show Action Buttons', 'wasmo-theme' ) }
                            checked={ showButtons }
                            onChange={ ( value ) => setAttributes( { showButtons: value } ) }
                            help={ __( 'Show "View All" and "Random Profile" buttons.', 'wasmo-theme' ) }
                        />

                        <ToggleControl
                            label={ __( 'Require Profile Image', 'wasmo-theme' ) }
                            checked={ requireImage }
                            onChange={ ( value ) => setAttributes( { requireImage: value } ) }
                            help={ __( 'Only show profiles that have an image uploaded.', 'wasmo-theme' ) }
                        />
                    </PanelBody>

                    <PanelBody title={ __( 'Filter Options', 'wasmo-theme' ) } initialOpen={ false }>
                        <SelectControl
                            label={ __( 'Taxonomy Filter', 'wasmo-theme' ) }
                            value={ taxonomyFilter }
                            options={ [
                                { label: __( 'None', 'wasmo-theme' ), value: '' },
                                { label: __( 'Mormon Spectrum', 'wasmo-theme' ), value: 'spectrum' },
                                { label: __( 'Shelf Items', 'wasmo-theme' ), value: 'shelf' },
                            ] }
                            onChange={ ( value ) => setAttributes( { taxonomyFilter: value } ) }
                            help={ __( 'Filter profiles by taxonomy.', 'wasmo-theme' ) }
                        />

                        { taxonomyFilter && (
                            <RangeControl
                                label={ __( 'Term ID', 'wasmo-theme' ) }
                                value={ termId }
                                onChange={ ( value ) => setAttributes( { termId: value } ) }
                                min={ 0 }
                                max={ 9999 }
                                help={ __( 'Enter the taxonomy term ID to filter by.', 'wasmo-theme' ) }
                            />
                        ) }
                    </PanelBody>
                </InspectorControls>

                <div className="user-directory-preview">
                    <div className="preview-header">
                        <span className="preview-icon">👥</span>
                        <h3>{ __( 'User Directory', 'wasmo-theme' ) }</h3>
                        <span className="preview-badge">{ maxProfiles } profiles</span>
                    </div>
                    
                    <div className="preview-meta">
                        <span className={ `context-badge context-${ context }` }>
                            { context === 'widget' ? '📦 Widget' : '📋 Full Directory' }
                        </span>
                        { showLoadMore && <span className="feature-badge">⏬ Load More</span> }
                        { showButtons && <span className="feature-badge">🔘 Buttons</span> }
                        { !requireImage && <span className="feature-badge feature-warning">🖼️ No image required</span> }
                        { taxonomyFilter && (
                            <span className="filter-badge">🏷️ { taxonomyFilter }: { termId }</span>
                        ) }
                    </div>

                    <div className="preview-users">
                        { users && users.length > 0 ? (
                            users.slice( 0, displayCount ).map( ( user ) => (
                                <div key={ user.id } className="preview-user" title={ user.name }>
                                    <img 
                                        src={ user.avatar_urls?.['96'] || user.avatar_urls?.['48'] } 
                                        alt={ user.name }
                                        className="preview-avatar"
                                    />
                                    <span className="preview-name">{ user.name.split(' ')[0] }</span>
                                </div>
                            ) )
                        ) : (
                            [ ...Array( displayCount ) ].map( ( _, i ) => (
                                <div key={ i } className="preview-user placeholder">
                                    <div className="preview-avatar-placeholder"></div>
                                </div>
                            ) )
                        ) }
                    </div>

                    { showButtons && (
                        <div className="preview-buttons">
                            <span className="preview-button">View All Profiles</span>
                            <span className="preview-button">Random Profile</span>
                        </div>
                    ) }
                </div>
            </div>
        );
    },

    save: () => {
        // Server-side rendering, so return null
        return null;
    }
} );
