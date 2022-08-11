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
function addFilterAttribute(settings) {
    if (typeof settings.attributes !== 'undefined') {
        settings.attributes = Object.assign(settings.attributes, {
            hideOnMobile: {
                type: 'boolean',
            },
            onlyLoggedIn: {
                type: 'boolean',
            },
            onlyOn: {
                type: 'array'
            },
            phpFilters: {
                type: 'array'
            }
        });
    }
    return settings;
}
 
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'sim/block-filter-attribute',
    addFilterAttribute
);

// Add controls to panel
const blockFilterControls = createHigherOrderComponent((BlockEdit) => {
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

        if(attributes.onlyOn == undefined){
            attributes.onlyOn = [];
        }

        /** FUNCTIONS */
        const [ searchTerm, setSearchTerm ]     = useState( '' );

        // Selected page list
        const { initialSelectedPages, selectedPagesResolved} = useSelect(
            ( select) => {
                // Find all selected pages
                const selectedPagesArgs = [ 'postType', 'page', {include : attributes.onlyOn} ];

                return {
                    initialSelectedPages: select( coreDataStore ).getEntityRecords(
                        ...selectedPagesArgs
                    ),
                    selectedPagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        selectedPagesArgs
                    )
                };
            },
            []
        );

        // Search page list
        const { pages, pagesResolved } = useSelect(
            ( select) => {
                // do not show results if not searching
                if ( !searchTerm ) {
                    return{
                        pages: [],
                        pagesResolved: true
                    }
                }

                // find all pages excluding the already selected pages
                const query = {
                    exclude : attributes.onlyOn,
                    search  : searchTerm,
                    per_page: 100,
                    orderby : 'relevance'
                };

                const pagesArgs         = [ 'postType', 'page', query ];
                console.log(pagesArgs);

                return {
                    pages: select( coreDataStore ).getEntityRecords(
                        ...pagesArgs
                    ),
                    pagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        pagesArgs
                    )
                };
            },
            [ searchTerm ]
        );

        const PageSelected = function(checked){
            if(checked){
                // Add to stored page ids
                setAttributes({onlyOn: [...attributes.onlyOn, this]});

                // Add to selected pages list
                setSelectedPages([...selectedPages, pages.find( p => p.id == this)]);
            }else{
                setAttributes({onlyOn: attributes.onlyOn.filter( p => {return p != this} )});
            }
        }

        const GetSelectedPagesControls = function(){
            if(attributes.onlyOn.length > 0){
                return (
                    <>
                        <i> {__('Currently selected pages', 'sim')}:</i>
                        <br></br>
                        
                        <BuildCheckboxControls hasResolved={ selectedPagesResolved } items={initialSelectedPages} showNoResults={false}/>
                    </>
                );
            }else{
                return '';
            }
        }

        const BuildCheckboxControls = function({ hasResolved, items, showNoResults= true }){
            if ( ! hasResolved ) {
                return(
                    <>
                    <Spinner />
                    <br></br>
                    </>
                );
            }
        
            if ( ! items?.length ) {
                if(showNoResults){
                    if ( !searchTerm ) {
                        return '';
                    }
                    return <div> {__('No search results', 'sim')}</div>;
                }

                return '';
            }
            
            return items?.map( ( page ) => {
            
                return (<CheckboxControl
                    label		= {decodeEntities( page.title.rendered )}
                    onChange	= {PageSelected.bind(page.id)}
                    checked		= {attributes.onlyOn.includes(page.id)}
                />)
            } )
        }

        const onPhpFiltersChanged	= function(newValue){
            let oldValue    = this;

            let newFilters  = [...attributes.phpFilters];
            // add a new value
            if(oldValue == '' && !attributes.phpFilters.includes(newValue)){
                newFilters.push(newValue);
            // value removed
            }else if(newValue == ''){
                newFilters   = attributes.phpFilters.filter(el => el != oldValue);
            // value changed
            }else{
                let index   = attributes.phpFilters.findIndex(el => el == oldValue);
                newFilters[index]  = newValue;
            }
    
            setAttributes({ phpFilters: newFilters });

            setPhpFilter('');
    
            setPageFilters(createFilterControls(newFilters));
        }

        const createFilterControls  = function(filters){
            return filters.map( filter =>
                <InputControl
                    isPressEnterToChange={true}
                    value={ filter }
                    onChange={ onPhpFiltersChanged.bind(filter) }
                />
            )
        };

        /** Variables */    
        let phpFilterControls   = '';
        if(attributes.phpFilters != undefined){
            phpFilterControls   = createFilterControls(attributes.phpFilters);
        }else{
            attributes.phpFilters   = [];
        }

        /** HOOKS */
        const [ selectedPages, setSelectedPages ]                   = useState( [] );
        const [ selectedPagesControls, setSelectedPagesControls ]   = useState( GetSelectedPagesControls() );
        const [ pageFilters, setPageFilters ]                       = useState( phpFilterControls );
        const [ phpFilter, setPhpFilter]                            = useState('');

        // Update selectedPagesControls on page resolve
        useEffect(() => {
            setSelectedPages(initialSelectedPages);
        }, [ selectedPagesResolved ]);

        // Update selectedPagesControls on check/uncheck
        useEffect(() => {            
            setSelectedPages( selectedPages.filter( p => {return attributes.onlyOn.includes(p.id)} ));
        }, [ attributes.onlyOn ]);

        useEffect( 
            () => {
                setSelectedPagesControls(BuildCheckboxControls({hasResolved: selectedPagesResolved, items: selectedPages, showNoResults: false}));
            }, 
            [selectedPages]
        );

        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                	<PanelBody title={ __( 'Block Visibility', 'sim' ) }>
                        <ToggleControl
                            label={__('Hide on mobile', 'sim')}
                            checked={!!attributes.hideOnMobile}
                            onChange={() => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />

                        <ToggleControl
                            label={__('Hide if not logged in', 'sim')}
                            checked={!!attributes.onlyLoggedIn}
                            onChange={() => setAttributes({ onlyLoggedIn: !attributes.onlyLoggedIn })}
                        />
                        
                        <InputControl
                            isPressEnterToChange={true}
                            label="Add php filters by name. I.e 'is_tax'"
                            value={ phpFilter }
                            onChange={onPhpFiltersChanged.bind('')}
                        />

                        {pageFilters}

                        <strong>{__('Select pages', 'sim')}</strong><br></br>
                        {__('Select pages you want this widget to show on', 'sim')}.<br></br>
                        {__('Leave empty for all pages', 'sim')}<br></br>
                        <br></br>
                        {selectedPagesControls}
                        <i>{__('Use searchbox below to search for more pages to include', 'sim')}</i>
                        < SearchControl onChange={ setSearchTerm } value={ searchTerm } />
                        < BuildCheckboxControls hasResolved= {pagesResolved} items= {pages} />
	                </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'blockFilterControls');
 
wp.hooks.addFilter(
    'editor.BlockEdit',
    'sim/block-filter-controls',
    blockFilterControls
);