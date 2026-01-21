import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, RangeControl, ToggleControl, FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

// Import styles
import './editor.scss';
import './style.scss';

registerBlockType('wasmo/post-display', {
    // Dynamic block title in editor outline
    __experimentalLabel: (attributes) => {
        if (attributes.title) {
            return `Post Display: ${attributes.title}`;
        }
        return 'Post Display';
    },

    edit: ({ attributes, setAttributes }) => {
        const {
            title, headingLevel, showTitle, postsToShow, categoryId, tagId,
            displayLayout, gridSize, showExcerpt, excerptLength, showDate, showAuthor, showFeaturedImage,
            featuredImageAlign, showButton, buttonText, buttonUrl, orderBy, order,
            description, excludeCategoryIds
        } = attributes;

        const blockProps = useBlockProps();

        // Get categories
        const categories = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'category', {
                per_page: -1,
                orderby: 'name',
                order: 'asc',
            });
        }, []);

        // Get tags
        const tags = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'post_tag', {
                per_page: -1,
                orderby: 'name',
                order: 'asc',
            });
        }, []);

        // Get preview posts
        const posts = useSelect((select) => {
            const queryArgs = {
                per_page: Math.min(postsToShow, 5),
                orderby: orderBy,
                order: order.toLowerCase(),
            };
            if (categoryId > 0) {
                queryArgs.categories = [categoryId];
            }
            if (tagId > 0) {
                queryArgs.tags = [tagId];
            }
            if (excludeCategoryIds && excludeCategoryIds.length > 0) {
                queryArgs.categories_exclude = excludeCategoryIds;
            }
            return select('core').getEntityRecords('postType', 'post', queryArgs);
        }, [postsToShow, categoryId, tagId, orderBy, order, excludeCategoryIds]);

        // Get selected category/tag names
        const selectedCategory = categories?.find(c => c.id === categoryId);
        const selectedTag = tags?.find(t => t.id === tagId);

        // Get excluded category names for display
        const excludedCategoryNames = (excludeCategoryIds || [])
            .map(id => categories?.find(c => c.id === id)?.name)
            .filter(Boolean);

        // All category names for suggestions
        const allCategoryNames = (categories || []).map(cat => cat.name);

        const categoryOptions = [
            { label: __('All Categories', 'wasmo-theme'), value: 0 },
            ...(categories || []).map(cat => ({
                label: cat.name,
                value: cat.id,
            })),
        ];

        const tagOptions = [
            { label: __('All Tags', 'wasmo-theme'), value: 0 },
            ...(tags || []).map(tag => ({
                label: tag.name,
                value: tag.id,
            })),
        ];

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Content Settings', 'wasmo-theme')} initialOpen={true}>
                        <ToggleControl
                            label={__('Show Title', 'wasmo-theme')}
                            checked={showTitle}
                            onChange={(value) => setAttributes({ showTitle: value })}
                        />

                        {showTitle && (
                            <>
                                <TextControl
                                    label={__('Title', 'wasmo-theme')}
                                    value={title}
                                    onChange={(value) => setAttributes({ title: value })}
                                />
                                <RangeControl
                                    label={__('Heading Level', 'wasmo-theme')}
                                    value={headingLevel}
                                    onChange={(value) => setAttributes({ headingLevel: value })}
                                    min={1}
                                    max={6}
                                />
                                <TextControl
                                    label={__('Description (optional)', 'wasmo-theme')}
                                    value={description}
                                    onChange={(value) => setAttributes({ description: value })}
                                    help={__('Optional intro text displayed below the title.', 'wasmo-theme')}
                                />
                            </>
                        )}

                        <SelectControl
                            label={__('Category', 'wasmo-theme')}
                            value={categoryId}
                            options={categoryOptions}
                            onChange={(value) => setAttributes({ categoryId: parseInt(value, 10) })}
                        />

                        <SelectControl
                            label={__('Tag', 'wasmo-theme')}
                            value={tagId}
                            options={tagOptions}
                            onChange={(value) => setAttributes({ tagId: parseInt(value, 10) })}
                        />

                        <FormTokenField
                            label={__('Exclude Categories', 'wasmo-theme')}
                            value={excludedCategoryNames}
                            suggestions={allCategoryNames}
                            onChange={(tokens) => {
                                const newExcludeIds = tokens
                                    .map(name => categories?.find(c => c.name === name)?.id)
                                    .filter(Boolean);
                                setAttributes({ excludeCategoryIds: newExcludeIds });
                            }}
                            __experimentalExpandOnFocus={true}
                            __experimentalShowHowTo={false}
                        />

                        <RangeControl
                            label={__('Number of Posts', 'wasmo-theme')}
                            value={postsToShow}
                            onChange={(value) => setAttributes({ postsToShow: value })}
                            min={1}
                            max={24}
                        />
                    </PanelBody>

                    <PanelBody title={__('Display Settings', 'wasmo-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Layout', 'wasmo-theme')}
                            value={displayLayout}
                            options={[
                                { label: __('List', 'wasmo-theme'), value: 'list' },
                                { label: __('Grid', 'wasmo-theme'), value: 'grid' },
                            ]}
                            onChange={(value) => setAttributes({ displayLayout: value })}
                        />

                        {displayLayout === 'grid' && (
                            <SelectControl
                                label={__('Grid Size', 'wasmo-theme')}
                                value={gridSize}
                                options={[
                                    { label: __('Small (6 columns)', 'wasmo-theme'), value: 'small' },
                                    { label: __('Medium (4 columns)', 'wasmo-theme'), value: 'medium' },
                                    { label: __('Large (3 columns)', 'wasmo-theme'), value: 'large' },
                                ]}
                                onChange={(value) => setAttributes({ gridSize: value })}
                                help={__('Number of columns on desktop. Stacks on mobile.', 'wasmo-theme')}
                            />
                        )}

                        <ToggleControl
                            label={__('Show Featured Image', 'wasmo-theme')}
                            checked={showFeaturedImage}
                            onChange={(value) => setAttributes({ showFeaturedImage: value })}
                        />

                        {showFeaturedImage && (
                            <SelectControl
                                label={__('Image Alignment', 'wasmo-theme')}
                                value={featuredImageAlign}
                                options={[
                                    { label: __('Left', 'wasmo-theme'), value: 'left' },
                                    { label: __('Top', 'wasmo-theme'), value: 'top' },
                                    { label: __('Right', 'wasmo-theme'), value: 'right' },
                                ]}
                                onChange={(value) => setAttributes({ featuredImageAlign: value })}
                            />
                        )}

                        <ToggleControl
                            label={__('Show Excerpt', 'wasmo-theme')}
                            checked={showExcerpt}
                            onChange={(value) => setAttributes({ showExcerpt: value })}
                        />

                        {showExcerpt && (
                            <RangeControl
                                label={__('Excerpt Length (words)', 'wasmo-theme')}
                                value={excerptLength}
                                onChange={(value) => setAttributes({ excerptLength: value })}
                                min={5}
                                max={100}
                            />
                        )}

                        <ToggleControl
                            label={__('Show Date', 'wasmo-theme')}
                            checked={showDate}
                            onChange={(value) => setAttributes({ showDate: value })}
                        />

                        <ToggleControl
                            label={__('Show Author', 'wasmo-theme')}
                            checked={showAuthor}
                            onChange={(value) => setAttributes({ showAuthor: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Sort Options', 'wasmo-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Order By', 'wasmo-theme')}
                            value={orderBy}
                            options={[
                                { label: __('Date', 'wasmo-theme'), value: 'date' },
                                { label: __('Title', 'wasmo-theme'), value: 'title' },
                                { label: __('Modified', 'wasmo-theme'), value: 'modified' },
                                { label: __('Random', 'wasmo-theme'), value: 'rand' },
                            ]}
                            onChange={(value) => setAttributes({ orderBy: value })}
                        />

                        <SelectControl
                            label={__('Order', 'wasmo-theme')}
                            value={order}
                            options={[
                                { label: __('Newest First', 'wasmo-theme'), value: 'DESC' },
                                { label: __('Oldest First', 'wasmo-theme'), value: 'ASC' },
                            ]}
                            onChange={(value) => setAttributes({ order: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Button Settings', 'wasmo-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Button', 'wasmo-theme')}
                            checked={showButton}
                            onChange={(value) => setAttributes({ showButton: value })}
                        />

                        {showButton && (
                            <>
                                <TextControl
                                    label={__('Button Text', 'wasmo-theme')}
                                    value={buttonText}
                                    onChange={(value) => setAttributes({ buttonText: value })}
                                />
                                <TextControl
                                    label={__('Button URL', 'wasmo-theme')}
                                    value={buttonUrl}
                                    onChange={(value) => setAttributes({ buttonUrl: value })}
                                    help={__('Leave empty to auto-link to category/tag archive.', 'wasmo-theme')}
                                />
                            </>
                        )}
                    </PanelBody>
                </InspectorControls>

                <div className="post-display-preview">
                    {showTitle && (
                        <div className="preview-title-section">
                            <div className="preview-title">
                                <span className="heading-tag">H{headingLevel}</span>
                                <span className="title-text">{title || __('Latest Posts', 'wasmo-theme')}</span>
                            </div>
                            {description && (
                                <p className="preview-description">{description}</p>
                            )}
                        </div>
                    )}

                    <div className="preview-filters">
                        {selectedCategory && (
                            <span className="filter-badge filter-category">
                                📁 {selectedCategory.name}
                            </span>
                        )}
                        {selectedTag && (
                            <span className="filter-badge filter-tag">
                                🏷️ {selectedTag.name}
                            </span>
                        )}
                        {excludedCategoryNames.length > 0 && (
                            <span className="filter-badge filter-exclude">
                                🚫 {excludedCategoryNames.join(', ')}
                            </span>
                        )}
                        {!selectedCategory && !selectedTag && excludedCategoryNames.length === 0 && (
                            <span className="filter-badge filter-all">All Posts</span>
                        )}
                        <span className="filter-count">
                            {postsToShow} posts · {displayLayout}{displayLayout === 'grid' ? ` (${gridSize})` : ''}
                        </span>
                    </div>

                    <div className={`preview-posts layout-${displayLayout}${displayLayout === 'grid' ? ` grid-${gridSize}` : ''}`}>
                        {posts && posts.length > 0 ? (
                            posts.slice(0, 3).map((post) => (
                                <div key={post.id} className="preview-post">
                                    {showFeaturedImage && (
                                        <div className="preview-post-image"></div>
                                    )}
                                    <div className="preview-post-content">
                                        <div className="preview-post-title">{post.title.rendered}</div>
                                        {showDate && (
                                            <div className="preview-post-date">
                                                {new Date(post.date).toLocaleDateString()}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="no-posts">{__('No posts found.', 'wasmo-theme')}</p>
                        )}
                        {posts && posts.length > 3 && (
                            <div className="preview-more">+ {posts.length - 3} more...</div>
                        )}
                    </div>

                    {showButton && (
                        <div className="preview-button">
                            <span className="button-preview">{buttonText}</span>
                        </div>
                    )}
                </div>
            </div>
        );
    },

    save: () => {
        return null;
    }
});
