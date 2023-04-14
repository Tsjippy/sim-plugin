import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import './editor.scss';
import { useState, useEffect} from "@wordpress/element";
import { SearchControl,PanelBody, Spinner, CheckboxControl } from "@wordpress/components";
import { decodeEntities } from '@wordpress/html-entities';
import apiFetch from "@wordpress/api-fetch";

const Edit = ({attributes, setAttributes}) => {
	const { page, hide, newline, content } = attributes;

	let noPostString	= __('Please select a page...', 'sim');

	let initialContent;

	let parsedPage		= {};

	try{
		parsedPage			= JSON.parse(page);
	}catch (error) {
		console.error('not a valid page');
	}

	if(parsedPage.post_content == undefined || content == undefined){
		initialContent	= noPostString;
	}else{
		initialContent	= wp.element.RawHTML( { children: content } );
	}

	const [ searchTerm, setSearchTerm ]		= useState( '' );
	const [ pageContent, setPageContent ]   = useState( initialContent );
	const [ results, setResults ] 			= useState( false );

	const SetContent	= async function(id, collapsible=hide, linebreak=newline){
	
		setPageContent(	<Spinner /> );
	
		initialContent	= await apiFetch({
			path: sim.restApiPrefix+'/embedpage/result',
			method: 'POST',
			data: { 
				id: id,
				collapsible: collapsible,
				linebreak: linebreak
			},
		});

		if(initialContent.trim() == ''){
			initialContent = "<div class='error'>Empty post</div>";
		}

		setAttributes({ content: initialContent});
	
		setPageContent(wp.element.RawHTML( { children: initialContent }));
	}

	useEffect( 
		() => {
			async function getResults(){
				setResults( false );
				const response = await apiFetch({
					path: sim.restApiPrefix+'/embedpage/find',
					method: 'POST',
					data: { 
						search: searchTerm
					},
				});
				setResults( response );
			}
			getResults();
		} ,
		[searchTerm]
	);

	const PageSelected	= async function(selected, post){
		if(selected){
			SetContent(post.ID);
			setAttributes({ page: JSON.stringify(post)});
		}else{
			setAttributes({ page: null });
			setPageContent( noPostString );
		}
	}

	const VisibilityChanged	= async function(checked){
		setAttributes({ hide: checked});

		SetContent(parsedPage.ID, checked);
	}

	const LineBreakChanged	= async function(checked){
		setAttributes({ newline: checked});

		SetContent(parsedPage.ID, hide, checked);
	}

	const BuildCheckboxControls = function(){
		if(page == '{}'){
			return '';
		}

		return (
			<>
			Currently embeded page:
			<CheckboxControl
				label		= { decodeEntities( parsedPage.post_title ) }
				onChange	= { (value) => PageSelected( value, parsedPage ) }
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
				label		= { decodeEntities( p.post_title )}
				onChange	= { (value) => PageSelected( value, p ) }
				checked		= { attributes.page==p}
			/>)
		} )
	}

	const SearchPage	= (hideIfFound) => {
		if(pageContent != noPostString && hideIfFound){
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
					<CheckboxControl
						label		= { __('Only show contents on hover') }
						onChange	= { (checked) => VisibilityChanged(checked) }
						checked		= { hide }
					/>

					<CheckboxControl
						label		= { __('Add a line break') }
						onChange	= { (checked) => LineBreakChanged(checked) }
						checked		= { newline }
					/>

					< BuildCheckboxControls  />
					<i>{__('Use searchbox below to search for a page', 'sim')}</i>
					{ SearchPage(false) }
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				{ SearchPage(true) }
				{pageContent}
			</div>
		</>
	);
}

export default Edit;
