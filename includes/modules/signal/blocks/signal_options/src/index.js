const { __ }                            = wp.i18n;
const { createHigherOrderComponent }    = wp.compose;
const { Fragment }                      = wp.element;
const { InspectorControls }             = wp.blockEditor;
const { PanelBody, ToggleControl, CheckboxControl }      = wp.components;
import { SearchControl, Spinner, __experimentalInputControl as InputControl } from '@wordpress/components';
import {useState, useEffect } from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';	

// Add attributes
function addSignalAttribute(settings) {
    if (typeof settings.attributes !== 'undefined') {
        settings.attributes = Object.assign(settings.attributes, {
            send_signal: {
                type: 'boolean',
            },
            signalmessagetype: {
                type: 'string',
            },
            signal_extra_message: {
                type: 'string'
            }
        });
    }
    return settings;
}
 
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'sim/signal-attribute',
    addSignalAttribute
);

// Add controls to panel
const signalControls = createHigherOrderComponent((BlockEdit) => {
    return ( props ) => {
        const { attributes, setAttributes, isSelected } = props;

        // Only work on selected blocks
        if(!isSelected){
            return (
                <Fragment>
                    <BlockEdit { ...props } />
                </Fragment>
            );
        }

        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );
    
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

        const sendSignal			= meta[ 'send_signal' ];
	    const signalMessageType		= meta[ 'signal_message_type' ];
	    const signalExtraMessage	= meta[ 'signal_extra_message' ];

        const updateMetaValue = ( value, key ) => {
            let newMeta	= { ...meta };

            newMeta[key]	= value;
    
            setMeta( newMeta );
        };	
        
        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                	<PanelBody title={ __( 'Signal Options', 'sim' ) }>
                        <ToggleControl
                            label={__('Send signal message on publish', 'sim')}
                            checked={sendSignal}
                            onChange={(value) => updateMetaValue(value, 'send_signal')}
                        />
                        
                        <RadioControl
                            selected= { signalMessageType }
                            options = {[
                                { label: __('Send a summary'), value: 'summary' },
                                { label: __('Send the whole post content'), value: 'all' }
                            ]}
                            onChange={ (value) => updateMetaValue( value, 'signal_message_type')}
                        />
                        
                        <TextareaControl
                            label={ __('Add this sentence to the signal message') }
                            value={ signalExtraMessage }
                            onChange={ (value) => updateMetaValue( value, 'signal_extra_message') }
                        />
	                </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'signalControls');
 
wp.hooks.addFilter(
    'editor.BlockEdit',
    'sim/signal-controls',
    signalControls
);