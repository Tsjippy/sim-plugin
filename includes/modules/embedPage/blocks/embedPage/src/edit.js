import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import './editor.scss';
import { useState, useEffect} from "@wordpress/element";
import { SearchControl,PanelBody, Spinner, CheckboxControl } from "@wordpress/components";
import { decodeEntities } from '@wordpress/html-entities';
import apiFetch from "@wordpress/api-fetch";


const Edit = ({attributes, setAttributes}) => {
	const {page} = attributes;

	let content;

	if(JSON.parse(page).post_content == undefined){
		content	= '';
	}else{
		content	= JSON.parse(page).post_content ;
	}

	const [ searchTerm, setSearchTerm ]		= useState( '' );
	const [ embededPage, setEmbededPage ]   = useState( '' );
	const [ pageContent, setPageContent ]   = useState( content );
	const [ results, setResults ] 			= useState( false );

	useEffect( 
		async () => {
			setResults( false );
			const response = await apiFetch({
				path: sim.restApiPrefix+'/embedpage/find',
				method: 'POST',
    			data: { 
					search: searchTerm
				},
			});
			setResults( response );
		} ,
		[searchTerm]
	);

	const PageSelected	= function(selected){
		if(selected){
			setAttributes({ page: JSON.stringify(this)});
			setPageContent(this.post_content)
		}else{
			setAttributes({ page: null });
		}
	}

	const BuildCheckboxControls = function(){
		if(page == undefined){
			return '';
		}

		return (
			<>
			Currently embeded page:
			<CheckboxControl
				label		= {decodeEntities( JSON.parse(page).post_title )}
				onChange	= {PageSelected.bind(page)}
				checked		= {true}
			/>
			</>
		)
	}

	const SearchResults	= function({ pageList }){
		if ( ! results ) {
			return(
				<>
				<Spinner />
				<br></br>
				</>
			);
		}

		if ( ! pageList?.length ) {
			if ( !searchTerm ) {
				return '';
			}
			return <div> {__('No search results', 'sim')}</div>;
		}
		
		return results?.map( ( p ) => {
			return (
			<CheckboxControl
				label		= {decodeEntities( p.post_title )}
				onChange	= {PageSelected.bind(p)}
				checked		= {attributes.page==p}
			/>)
		} )
	}

	useEffect(
		() => {
			if(pageContent != null && pageContent != ""){
				setEmbededPage(wp.element.RawHTML( { children: pageContent }));
			}else if(!pageContent || pageContent == null || pageContent == ''){
				setEmbededPage(__('Please select a page...', 'sim'));
			}else{
				setEmbededPage(wp.element.RawHTML( { children: pageContent[0].content.raw }));
			}
			
		},
		[pageContent]
	)

	const SearchPage	= (hideIfFound) => {
		if(pageContent != "" && hideIfFound){
			return '';
		}
		return(
			<>
				< SearchControl onChange={setSearchTerm} value={ searchTerm } autoFocus={true}/>
				< SearchResults pageList= {results} />
			</>
		)
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Page Embed Settings', 'sim' ) }>
					< BuildCheckboxControls  />
					<i>{__('Use searchbox below to search for a page', 'sim')}</i>
					{ SearchPage(false) }
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				{ SearchPage(true) }
				{embededPage}
			</div>
		</>
	);
}

export default Edit;
