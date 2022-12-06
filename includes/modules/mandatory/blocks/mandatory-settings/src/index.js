const { __ }                            = wp.i18n;
const { registerPlugin }                = wp.plugins;
import { CheckboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEntityProp } from '@wordpress/core-data';

registerPlugin( 'mandatory-audience', {
    render: function(){
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

        if(meta == undefined){
            return '';
        }

        const audience	= meta[ 'audience' ] == undefined ? {} : JSON.parse(meta[ 'audience' ]);

        const updateMetaValue = ( selected, key ) => {
            let newMeta	= { ...meta };

            let newAudience  = { ...audience };
            // add a new value
            if(selected){
                newAudience[key] = key;
            // value removed
            }else{
                delete newAudience[key]
            }

            newMeta['audience']	= JSON.stringify(newAudience);
    
            setMeta( newMeta );
        };

        const   CheckBoxes  = () => {
            return Object.keys(mandatory).map( index => (
                <CheckboxControl
                    key         = {index}
                    label		= { mandatory[index] }
                    onChange	= { (selected) => updateMetaValue(selected, index) }
                    checked		= {audience[index] != undefined}
                />
            ));
        }
    
        return (
                <PluginDocumentSettingPanel
                    name="mandatory-audience"
                    title={ __( 'Mandatory settings', 'sim' ) }
                    className="mandatory-audience"
                >
                    { CheckBoxes() }
                </PluginDocumentSettingPanel>
        );
    },
    icon: 'groups'
} );