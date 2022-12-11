import React, { useEffect, useState } from 'react';
import {
	Button,
	Card,
	CardActions,
	CardContent,
	Typography,
	TextField,
	Alert,
	Box,
	CircularProgress,
	LinearProgress,
} from '@mui/material';
import { __, sprintf } from '@wordpress/i18n';
import { nonce, gitkitOptions } from '@gitkit';

const PluginUploaderForm = () => {
	const [ resultMessage, setResultMessage ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isErrorMessage, setIsErrorMessage ] = useState( false );

	useEffect( () => {
		// if ( ! _.isEmpty( resultMessage ) ) {
		setTimeout( () => {
			setResultMessage( '' );
		}, 2000 );
		// }
	}, [ resultMessage ] );

	const onSubmit = ( e ) => {
		e.persist();
		e.preventDefault();

		setIsLoading( true );

		const data = new FormData( e.target );
		const formObject = Object.fromEntries( data.entries() );

		const ajaxData = {
			nonce,
			action: 'gitkit_download_and_upload_plugin',
		};

		wp.ajax.post( { ...formObject, ...ajaxData } ).done( ( message ) => {
			setIsErrorMessage( false );
			setResultMessage( message );
			setIsLoading( false );
		} ).fail( ( error ) => {
			// eslint-disable-next-line no-console
			console.log( error );
			setIsLoading( false );
			setResultMessage( error );
			setIsErrorMessage( true );
		} );
	};

	return <div className="gitkit-plugin-uploader-wrapper">
		{ resultMessage ? <Alert severity={ isErrorMessage ? 'error' : 'success' }>{ resultMessage }</Alert> : '' }
		<Card sx={ { minWidth: 275 } }>
			<form onSubmit={ onSubmit }>
				<CardContent>
					<Typography variant="h6" component="div">
						Gitkit Plugin Uploader
					</Typography>
					<br />
					<Box sx={ { display: 'flex', flexDirection: 'row' } } className="gitkit-plugin-uploader-box-wrapper">
						<div>
							<TextField
								id="gitkit-github-access-token"
								className="gitkit-github-access-token"
								label={ __( 'Plugin\'s github respository link', 'gitkit' ) }
								type="url"
								name="gitkit_github_repository_url"
								helperText=""
								required={ true }
							/>
						</div>
						<div>
							<TextField
								id="gitkit_github_branch_name"
								className="gitkit_github_branch_name"
								label={ __( 'Branch', 'gitkit' ) }
								type="text"
								name="gitkit_github_branch_name"
								required={ true }
							/>
						</div>
						<div>
							<Button variant="contained" color="success" type="submit">
								{ __( 'Upload', 'gitkit' ) }
							</Button>
						</div>
					</Box>
					{ isLoading && <LinearProgress /> }
				</CardContent>
			</form>
		</Card>
	</div>;
};

export default PluginUploaderForm;
