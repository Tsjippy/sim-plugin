const { __ }                            = wp.i18n;
const { registerPlugin }                = wp.plugins;
import { DatePicker, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEntityProp } from '@wordpress/core-data';

registerPlugin( 'expiry-date', {
    render: function(){
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );

        if(postType == null){
            return '';
        }
    
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

        const expiryDate	= meta[ 'expirydate' ];
        const staticContent	= meta[ 'static_content' ];

        const updateMetaValue = ( value, key) => {
            let newMeta	= { ...meta };

            newMeta[key]	= value;
    
            setMeta( newMeta );
        };
    
        return (
            <>
                <PluginDocumentSettingPanel
                    name="expiry-dates"
                    title={ __( 'Expiry date', 'sim' ) }
                    className="expiry-date"
                >
                    <DatePicker
                        currentDate={ expiryDate }
                        value={ expiryDate }
                        onChange={ ( value ) => updateMetaValue( value, 'expirydate' ) }
                    />
                </PluginDocumentSettingPanel>
                <PluginDocumentSettingPanel
                    name="static_content"
                    title={ __( 'Static content', 'sim' ) }
                    className="static_content"
                >
                    <ToggleControl
                        label={__('Do not send update warnings for this page', 'sim')}
                        checked={ staticContent }
                        onChange={( value ) => updateMetaValue( value, 'static_content' )}
                    />
                </PluginDocumentSettingPanel>
            </>
        );
    },
    icon: false
} );