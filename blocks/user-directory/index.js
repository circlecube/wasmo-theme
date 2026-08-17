import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl, FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useMemo, useState } from '@wordpress/element';

// Import styles
import './editor.scss';
import './style.scss';

const USERS_PER_PAGE = 100;
const MAX_USER_PAGES = 50;

function getUserLabel( user ) {
    if ( ! user ) {
        return '';
    }

    if ( user.slug ) {
        return `${ user.name } (@${ user.slug })`;
    }

    return user.name;
}

function findUserByLabel( users, label ) {
    return users.find( ( user ) => getUserLabel( user ) === label );
}

function mergeUsersById( ...userLists ) {
    const map = new Map();

    userLists.forEach( ( users ) => {
        ( users || [] ).forEach( ( user ) => {
            if ( user?.id ) {
                map.set( user.id, user );
            }
        } );
    } );

    return Array.from( map.values() ).sort( ( a, b ) => a.name.localeCompare( b.name ) );
}

async function fetchUsersPage( page, context = 'edit' ) {
    const params = new URLSearchParams( {
        per_page: String( USERS_PER_PAGE ),
        page: String( page ),
        orderby: 'name',
        order: 'asc',
        context,
    } );

    const response = await apiFetch( {
        path: `/wp/v2/users?${ params.toString() }`,
        parse: false,
    } );

    if ( ! response.ok ) {
        throw new Error( `Users request failed (${ response.status })` );
    }

    const users = await response.json();

    return {
        users: Array.isArray( users ) ? users : [],
        totalPages: parseInt( response.headers.get( 'X-WP-TotalPages' ) || '1', 10 ),
    };
}

async function fetchAllUsers() {
    const users = [];
    let page = 1;
    let totalPages = 1;

    while ( page <= totalPages && page <= MAX_USER_PAGES ) {
        const batch = await fetchUsersPage( page, 'edit' );
        totalPages = batch.totalPages;

        if ( batch.users.length ) {
            users.push( ...batch.users );
        }

        page += 1;
    }

    return users;
}

async function fetchUsersByIds( userIds ) {
    const ids = ( userIds || [] ).filter( Boolean );

    if ( ! ids.length ) {
        return [];
    }

    const params = new URLSearchParams( {
        include: ids.join( ',' ),
        per_page: String( ids.length ),
        context: 'edit',
    } );

    const users = await apiFetch( { path: `/wp/v2/users?${ params.toString() }` } );

    return Array.isArray( users ) ? users : [];
}

function useAllUsers( savedUserIds ) {
    const [ allUsers, setAllUsers ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ loadError, setLoadError ] = useState( '' );

    useEffect( () => {
        let cancelled = false;

        async function loadUsers() {
            setIsLoading( true );
            setLoadError( '' );

            try {
                const users = await fetchAllUsers();
                const savedUsers = await fetchUsersByIds( savedUserIds );

                if ( ! cancelled ) {
                    setAllUsers( mergeUsersById( users, savedUsers ) );
                    setIsLoading( false );
                }
            } catch ( error ) {
                if ( ! cancelled ) {
                    setLoadError( __( 'Unable to load users.', 'wasmo-theme' ) );
                    setIsLoading( false );
                }
            }
        }

        loadUsers();

        return () => {
            cancelled = true;
        };
    }, [ savedUserIds.join( ',' ) ] );

    return { allUsers, isLoading, loadError };
}

registerBlockType( 'wasmo/user-directory', {
    edit: ( { attributes, setAttributes } ) => {
        const { context, maxProfiles, showLoadMore, showButtons, taxonomyFilter, termId, requireImage, videoOnly, excludeUserIds, featuredUserIds } = attributes;
        const blockProps = useBlockProps();
        const savedUserIds = useMemo(
            () => [ ...( featuredUserIds || [] ), ...( excludeUserIds || [] ) ],
            [ featuredUserIds, excludeUserIds ]
        );
        const { allUsers, isLoading, loadError } = useAllUsers( savedUserIds );

        const previewUsers = useSelect( ( select ) => {
            return select( 'core' ).getUsers( {
                per_page: Math.min( maxProfiles, 12 ),
                orderby: 'registered',
                order: 'desc',
            } );
        }, [ maxProfiles ] );

        const usersForPreview = allUsers.length > 0 ? allUsers : ( previewUsers || [] );

        const usersById = useMemo( () => {
            const map = new Map();
            allUsers.forEach( ( user ) => map.set( user.id, user ) );
            return map;
        }, [ allUsers ] );

        const userSuggestions = useMemo(
            () => allUsers.map( ( user ) => getUserLabel( user ) ),
            [ allUsers ]
        );

        const excludedUserNames = ( excludeUserIds || [] )
            .map( ( id ) => getUserLabel( usersById.get( id ) ) )
            .filter( Boolean );
        const featuredUserNames = ( featuredUserIds || [] )
            .map( ( id ) => getUserLabel( usersById.get( id ) ) )
            .filter( Boolean );

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
                        <ToggleControl
                            label={ __( 'Video Stories Only', 'wasmo-theme' ) }
                            checked={ videoOnly }
                            onChange={ ( value ) => setAttributes( { videoOnly: value } ) }
                            help={ __( 'Only show profiles that include a video story.', 'wasmo-theme' ) }
                        />

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

                    <PanelBody title={ __( 'Profile Curation', 'wasmo-theme' ) } initialOpen={ false }>
                        <FormTokenField
                            label={ __( 'Featured Profiles (up to 3)', 'wasmo-theme' ) }
                            value={ featuredUserNames }
                            suggestions={ userSuggestions }
                            onChange={ ( tokens ) => {
                                const newFeaturedIds = tokens
                                    .slice( 0, 3 )
                                    .map( ( label ) => findUserByLabel( allUsers, label )?.id )
                                    .filter( Boolean );
                                setAttributes( { featuredUserIds: newFeaturedIds } );
                            } }
                            __experimentalExpandOnFocus={ true }
                            __experimentalShowHowTo={ false }
                            disabled={ !! loadError }
                            help={ isLoading ? __( 'Loading all users…', 'wasmo-theme' ) : loadError ? loadError : __( 'Pin up to 3 profiles to the top of the grid. Search by display name or username (@slug).', 'wasmo-theme' ) }
                        />

                        <FormTokenField
                            label={ __( 'Exclude Profiles', 'wasmo-theme' ) }
                            value={ excludedUserNames }
                            suggestions={ userSuggestions }
                            onChange={ ( tokens ) => {
                                const newExcludeIds = tokens
                                    .map( ( label ) => findUserByLabel( allUsers, label )?.id )
                                    .filter( Boolean );
                                setAttributes( { excludeUserIds: newExcludeIds } );
                            } }
                            __experimentalExpandOnFocus={ true }
                            __experimentalShowHowTo={ false }
                            disabled={ !! loadError }
                            help={ isLoading ? __( 'Loading all users…', 'wasmo-theme' ) : loadError ? loadError : __( 'Remove selected profiles from this grid. Search by display name or username (@slug).', 'wasmo-theme' ) }
                        />
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
                        { videoOnly && <span className="filter-badge">🎥 Video Only</span> }
                        { featuredUserNames.length > 0 && (
                            <span className="filter-badge">⭐ { featuredUserNames.join( ', ' ) }</span>
                        ) }
                        { excludedUserNames.length > 0 && (
                            <span className="filter-badge">🚫 { excludedUserNames.join( ', ' ) }</span>
                        ) }
                        { taxonomyFilter && (
                            <span className="filter-badge">🏷️ { taxonomyFilter }: { termId }</span>
                        ) }
                    </div>

                    <div className="preview-users">
                        { usersForPreview && usersForPreview.length > 0 ? (
                            usersForPreview.slice( 0, displayCount ).map( ( user ) => (
                                <div key={ user.id } className="preview-user" title={ getUserLabel( user ) }>
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
                            <span className="preview-button">Browse Stories</span>
                            <span className="preview-button">Random Story</span>
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
