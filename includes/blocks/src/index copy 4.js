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
            onlyOnHomePage: {
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
        const { attributes, setAttributes } = props;

        if(attributes.onlyOn == undefined){
            attributes.onlyOn = [];
        }

        /** FUNCTIONS */
        const [ searchTerm, setSearchTerm ]     = useState( '' );

        // Selected page list
        let { selectedPages, selectedPagesResolved} = useSelect(
            ( select) => {
                // Find all selected pages
                const selectedPagesArgs = [ 'postType', 'page', {include : attributes.onlyOn} ];

                return {
                    selectedPages: select( coreDataStore ).getEntityRecords(
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
                // find all pages excluding the already selected pages
                const query = {exclude : attributes.onlyOn};
                if ( searchTerm ) {
                    query.search = searchTerm;
                }
                const pagesArgs         = [ 'postType', 'page', query ];

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
            let newPages    = [...attributes.onlyOn];

            if(checked){
                // Add to stored page ids
                newPages.push(this);

                // Add to selected pages list
                selectedPages.push(pages.find( p => p.id == this));
            }else{
                newPages    = newPages.filter( p => {return p != this} );
            }

            setAttributes({onlyOn: newPages});
        }

        const PagesList = function ( { hasResolved, pageArray, showLoader = true } ) {
            console.log(pageArray);

            if ( ! hasResolved ) {
                return(
                    <>
                    <Spinner />
                    <br></br>
                    </>
                );
            }
        
            if ( ! pageArray?.length ) {
                if(showLoader){
                    return <div> {__('No search results', 'sim')}</div>;
                }

                return '';
            }
        
            return (
                pageArray?.map( ( page ) => {
        
                    return (<CheckboxControl
                        label		= {decodeEntities( page.title.rendered )}
                        onChange	= {PageSelected.bind(page.id)}
                        checked		= {attributes.onlyOn.includes(page.id)}
                    />)
                } )
            );
        }

        const GetSelectedPagesControls = function(){
            console.log(attributes.onlyOn)
            if(attributes.onlyOn.length > 0){
                return (
                    <>
                        <i> {__('Currently selected pages', 'sim')}:</i>
                        <br></br>
                        <PagesList hasResolved={ selectedPagesResolved } pageArray={selectedPages} props={ props } showLoader={false}/>
                    </>
                );
            }else{
                return '';
            }
        }

        const onPhpFiltersChanged	= function(newValue){
            let oldValue    = this;

            // add a new value
            if(oldValue == ''){
                attributes.phpFilters.push(newValue);
            // value removed
            }else if(newValue == ''){
                let index   = attributes.phpFilters.findIndex(el=>el==oldValue);
                attributes.phpFilters.splice(index, 1);
            // value changed
            }else{
                let index   = attributes.phpFilters.findIndex(el=>el==oldValue);
                attributes.phpFilters[index]  = newValue;
            }
    
            setAttributes({ phpFilters: attributes.phpFilters });
    
            setPageFilters(createFilterControls(attributes.phpFilters));
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
        }

        /** HOOKS */
        const [ selectedPagesControls, setSelectedPagesControls ]   = useState( GetSelectedPagesControls() );
        const [ pageFilters, setPageFilters ]                       = useState( phpFilterControls );

        // Update selectedPagesControls on page resolve
        useEffect(() => {
            setSelectedPagesControls(GetSelectedPagesControls());
        }, [ selectedPagesResolved ]);

        // Update selectedPagesControls on check/uncheck
        useEffect(() => {
            if(selectedPages == null){
                return;
            }
            
            setSelectedPagesControls( state => {
                console.log(state);

                return selectedPages.filter( p => {return attributes.onlyOn.includes(p.id)} )?.map( ( page ) => {
        
                    return (<CheckboxControl
                        label		= {decodeEntities( page.title.rendered )}
                        onChange	= {PageSelected.bind(page.id)}
                        checked		= {attributes.onlyOn.includes(page.id)}
                    />)
                } )
            });
        }, [ attributes.onlyOn ]);

        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                	<PanelBody title={ __( 'Block Visibility' ) }>
                    <ToggleControl
                            label={__('Hide on mobile', 'sim')}
                            checked={!!attributes.hideOnMobile}
                            onChange={(newval) => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />

                        <ToggleControl
                            label={__('Only on home page', 'sim')}
                            checked={!!attributes.onlyOnHomePage}
                            onChange={(newval) => setAttributes({ onlyOnHomePage: !attributes.onlyOnHomePage })}
                        />

                        <ToggleControl
                            label={__('Hide if not logged in', 'sim')}
                            checked={!!attributes.onlyLoggedIn}
                            onChange={(newval) => setAttributes({ onlyLoggedIn: !attributes.onlyLoggedIn })}
                        />
                        
                        <InputControl
                            isPressEnterToChange={true}
                            label="Add php filters like 'isPage(12)'"
                            value={ '' }
                            onChange={onPhpFiltersChanged.bind('')}
                        />

                        {pageFilters}

                        <strong>{__('Select pages', 'sim')}</strong><br></br>
                        {__('Select pages you want this widget to show on', 'sim')}.<br></br>
                        {__('Leave empty for all pages', 'sim')}<br></br>
                        <br></br>
                        {selectedPagesControls}
                        <i>{__('Use searchbox below to search for more pages to include', 'sim')}</i>
                        <SearchControl onChange={ setSearchTerm } value={ searchTerm } />
                        <PagesList hasResolved={ pagesResolved } pageArray={ pages } props={ props }/>
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