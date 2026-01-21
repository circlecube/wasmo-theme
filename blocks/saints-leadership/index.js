import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

// Import styles
import './editor.scss';
import './style.scss';

const leadershipLabels = {
    'first-presidency': 'First Presidency',
    'quorum-of-twelve': 'Quorum of the Twelve',
    'all-presidents': 'All Church Presidents',
};

registerBlockType('wasmo/saints-leadership', {
    __experimentalLabel: (attributes) => {
        const label = leadershipLabels[attributes.leadershipGroup] || 'Saints Leadership';
        return `Saints: ${label}`;
    },

    edit: ({ attributes, setAttributes }) => {
        const { leadershipGroup, showTitle, showDescription, showBadges, cardSize } = attributes;
        const blockProps = useBlockProps();

        // Determine preview content based on selection
        const getPreviewContent = () => {
            switch (leadershipGroup) {
                case 'first-presidency':
                    return {
                        title: 'The First Presidency',
                        description: 'The highest governing body of the Church',
                        count: 3,
                        layout: 'grid-3',
                        badges: ['First Counselor', 'President', 'Second Counselor'],
                    };
                case 'quorum-of-twelve':
                    return {
                        title: 'Quorum of the Twelve Apostles',
                        description: 'Listed in order of seniority',
                        count: 12,
                        layout: 'grid-6',
                        badges: Array.from({ length: 12 }, (_, i) => `${i + 1}`),
                    };
                case 'all-presidents':
                    return {
                        title: 'Church Presidents',
                        description: 'All presidents in chronological order',
                        count: 17,
                        layout: 'timeline',
                        badges: Array.from({ length: 17 }, (_, i) => `${i + 1}`),
                    };
                default:
                    return { title: '', description: '', count: 0, layout: '', badges: [] };
            }
        };

        const preview = getPreviewContent();

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Leadership Settings', 'wasmo-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Leadership Group', 'wasmo-theme')}
                            value={leadershipGroup}
                            options={[
                                { label: __('First Presidency', 'wasmo-theme'), value: 'first-presidency' },
                                { label: __('Quorum of the Twelve', 'wasmo-theme'), value: 'quorum-of-twelve' },
                                { label: __('All Church Presidents', 'wasmo-theme'), value: 'all-presidents' },
                            ]}
                            onChange={(value) => setAttributes({ leadershipGroup: value })}
                        />

                        <SelectControl
                            label={__('Card Size', 'wasmo-theme')}
                            value={cardSize}
                            options={[
                                { label: __('Small', 'wasmo-theme'), value: 'small' },
                                { label: __('Medium', 'wasmo-theme'), value: 'medium' },
                                { label: __('Large', 'wasmo-theme'), value: 'large' },
                            ]}
                            onChange={(value) => setAttributes({ cardSize: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Display Options', 'wasmo-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Title', 'wasmo-theme')}
                            checked={showTitle}
                            onChange={(value) => setAttributes({ showTitle: value })}
                        />

                        <ToggleControl
                            label={__('Show Description', 'wasmo-theme')}
                            checked={showDescription}
                            onChange={(value) => setAttributes({ showDescription: value })}
                        />

                        <ToggleControl
                            label={__('Show Position Badges', 'wasmo-theme')}
                            checked={showBadges}
                            onChange={(value) => setAttributes({ showBadges: value })}
                            help={__('Show numbered positions or role badges on cards.', 'wasmo-theme')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className={`saints-leadership-preview leadership-${leadershipGroup}`}>
                    <div className="preview-header">
                        <span className="preview-icon">⛪</span>
                        <h3>{leadershipLabels[leadershipGroup]}</h3>
                        <span className="preview-count">{preview.count} members</span>
                    </div>

                    {showTitle && (
                        <div className="preview-title">{preview.title}</div>
                    )}

                    {showDescription && (
                        <p className="preview-description">{preview.description}</p>
                    )}

                    <div className={`preview-cards ${preview.layout}`}>
                        {Array.from({ length: Math.min(preview.count, leadershipGroup === 'first-presidency' ? 3 : 6) }).map((_, i) => (
                            <div key={i} className="preview-card">
                                {showBadges && preview.badges[i] && (
                                    <span className="preview-badge">{preview.badges[i]}</span>
                                )}
                                <div className="preview-card-image"></div>
                                <div className="preview-card-name"></div>
                            </div>
                        ))}
                        {preview.count > 6 && leadershipGroup !== 'first-presidency' && (
                            <div className="preview-more">+{preview.count - 6} more</div>
                        )}
                    </div>
                </div>
            </div>
        );
    },

    save: () => {
        return null;
    }
});
