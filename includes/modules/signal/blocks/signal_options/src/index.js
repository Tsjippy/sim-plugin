const { __ }                 = wp.i18n;
import { ToggleControl, RadioControl, TextareaControl} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEntityProp } from '@wordpress/core-data';
import { registerPlugin } from '@wordpress/plugins';

const signalIcon = () => {
    return (
        <svg width="20" height="20">
            <g transform="matrix(0.15625 0 0 0.15625 0 0)">
                <path d="M48.64,1.87L50.079998 7.69C 44.415306 9.083115 38.994007 11.325134 34 14.34L34 14.34L30.92 9.2C 36.423363 5.877327 42.39759 3.4060478 48.64 1.87zM79.36 1.87L77.92 7.69C 83.58467 9.083107 89.00602 11.325146 94 14.34L94 14.34L97.1 9.200001C 91.59042 5.875473 85.60933 3.4041443 79.36 1.87zM9.2 30.92C 5.877327 36.423363 3.4060478 42.39759 1.87 48.64L1.87 48.64L7.69 50.079998C 9.083115 44.415306 11.325134 38.994007 14.34 34zM6 64C 5.998066 61.09113 6.215351 58.18621 6.65 55.31L6.65 55.31L0.72000027 54.41C -0.23995209 60.7673 -0.23995209 67.232704 0.72000027 73.59L0.72000027 73.59L6.65 72.689995C 6.2153587 69.81381 5.99807 66.908844 6 64zM97.08 118.8L94 113.66C 89.01223 116.673 83.59773 118.91499 77.94 120.310005L77.94 120.310005L79.380005 126.130005C 85.61551 124.59205 91.58283 122.120834 97.08 118.8zM122 64C 122.00194 66.90884 121.784645 69.81382 121.35 72.69L121.35 72.69L127.28 73.590004C 128.23996 67.232704 128.23996 60.767296 127.28 54.410004L127.28 54.410004L121.35 55.310005C 121.784645 58.186188 122.00194 61.091164 122 64zM126.13 79.36L120.31 77.92C 118.9169 83.58469 116.674866 89.006004 113.66 94L113.66 94L118.8 97.1C 122.12453 91.59043 124.595856 85.60934 126.13 79.36zM72.69 121.36C 66.92908 122.22673 61.070923 122.22673 55.310005 121.36L55.310005 121.36L54.410004 127.29C 60.767296 128.24995 67.232704 128.24995 73.590004 127.29zM110.69 98.41C 107.2307 103.09508 103.08789 107.23451 98.4 110.69L98.4 110.69L101.96 115.520004C 107.131454 111.71768 111.70241 107.1602 115.52 102zM98.4 17.31C 103.08865 20.76854 107.23146 24.91135 110.69 29.6L110.69 29.6L115.52 26C 111.7146 20.84277 107.15723 16.2854 102 12.48zM17.31 29.6C 20.76854 24.91135 24.91135 20.76854 29.6 17.31L29.6 17.31L26 12.48C 20.842766 16.285397 16.285397 20.842766 12.48 26zM118.8 30.92L113.66 34C 116.673 38.98777 118.91499 44.402264 120.310005 50.059998L120.310005 50.059998L126.130005 48.62C 124.59204 42.38449 122.120834 36.417175 118.8 30.92zM55.31 6.65C 61.07092 5.783268 66.92908 5.783268 72.69 6.65L72.69 6.65L73.590004 0.72000027C 67.232704 -0.23995209 60.767296 -0.23995209 54.410004 0.72000027zM20.39 117.11L8 120L10.89 107.61L5.05 106.24L2.16 118.63C 1.6867399 120.65097 2.2916193 122.77299 3.7593164 124.240685C 5.227014 125.70838 7.3490367 126.31326 9.37 125.84L9.37 125.84L21.75 123zM6.3 100.89L12.14 102.25L14.14 93.66C 11.2248535 88.76049 9.051266 83.45627 7.69 77.92L7.69 77.92L1.87 79.36C 3.1747017 84.66252 5.1577263 89.77468 7.77 94.57zM34.3 113.89L25.71 115.89L27.07 121.729996L33.39 120.259995C 38.185314 122.87227 43.297478 124.855286 48.6 126.159996L48.6 126.159996L50.039997 120.34C 44.515343 118.962456 39.224777 116.77547 34.34 113.85zM64 12C 45.07826 12.009804 27.655266 22.297073 18.507568 38.860645C 9.359871 55.42422 9.93145 75.64949 20 91.67L20 91.67L15 113L36.33 108C 55.03896 119.78357 79.1536 118.447586 96.446396 104.669464C 113.7392 90.89134 120.42809 67.684456 113.12143 46.816055C 105.81476 25.947659 86.110565 11.981804 64 12z" stroke="none" fill="#3A76F0" fill-rule="nonzero" />
            </g>
        </svg>
    );
};

// Add controls to panel
registerPlugin( 'signal-options', {
    render: function(){
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );

        if(postType == null){
            return '';
        }
    
        const [ meta, setMeta ]     = useEntityProp( 'postType', postType, 'meta' );

        const sendSignal			= meta[ 'send_signal' ];
	    let signalMessageType		= meta[ 'signal_message_type' ];
	    const signalExtraMessage	= meta[ 'signal_extra_message' ];
        const signalUrl	            = meta[ 'signal_url' ];

        if(signalMessageType != 'all'){
            signalMessageType   = 'summary';
        }

        const updateMetaValue = ( value, key ) => {
            let newMeta	= { ...meta };

            newMeta[key]	= value;

            console.log(newMeta);
    
            setMeta( newMeta );
        };	

        return (
            <PluginDocumentSettingPanel
                name="signal-options"
                title={ __( 'Signal Options', 'sim' ) }
                className="signal-options"
            >
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

                <br></br>
                
                <TextareaControl
                    label={ __('Add this sentence to the signal message:') }
                    value={ signalExtraMessage }
                    onChange={ (value) => updateMetaValue( value, 'signal_extra_message') }
                />

                <ToggleControl
                    label={__('Include the url in the message even if the whole content is posted', 'sim')}
                    checked={signalUrl}
                    onChange={(value) => updateMetaValue(value, 'signal_url')}
                />
            </PluginDocumentSettingPanel>
        );
    },
    icon: signalIcon()
});
 