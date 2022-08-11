import { __ } from '@wordpress/i18n';
import {useBlockProps} from "@wordpress/block-editor";
import './editor.scss';
import apiFetch from "@wordpress/api-fetch";
import {useState, useEffect} from "@wordpress/element";
import { Spinner } from '@wordpress/components';


const Edit = () => {

	const [html, setHtml] = useState(<Spinner />);

	useEffect( async () => {
		const statementHtml = await apiFetch({path: "/sim/v1/banking/get_statements"});
		setHtml(statementHtml);
	} , []);

	return (
		<>
			<div {...useBlockProps()}>
				{html}
			</div>
		</>
	);
}

export default Edit;
