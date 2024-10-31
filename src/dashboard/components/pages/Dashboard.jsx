/**
 * External dependencies.
 */
import { useState, useEffect } from "@wordpress/element";

/**
 * Internal dependencies.
 */
import { showPromiseToast, showToast } from "../../utils";
import { saveOptions, fetchOptions, APICheck } from "../../api/settings";
import { SettingsCard, TextInput } from "../templates";
import Layout from "../layout/Layout";

const pluginUrl = pluginData.pluginUrl || "/wp-content/plugins/openasset/";

const Dashboard = () => {
	const [processing, setProcessing] = useState(true);

	const [options, setOptions] = useState({
		"general-settings": {},
	});
	const [isLoading, setIsLoading] = useState(false);

	const updateOption = (value, id) => {
		setOptions({
			...options,
			"general-settings": {
				...options["general-settings"],
				[id]: value,
			},
		});
	};

	const onSave = async () => {
		if (!processing) {
			setIsLoading(true);
			// Prepare the data for the AJAX request

			let token_id = options["general-settings"]["your-real-token-id"];
			let api_token = options["general-settings"]["your-real-api-token"];
			let client_instance_url =
				options["general-settings"]["client-instance-url"];
			//check if fields are empty
			if (
				token_id == undefined ||
				api_token == undefined ||
				client_instance_url == undefined
			) {
				setIsLoading(false);
				showToast("Please fill in all fields", "error");
				return;
			}
			const res = await saveOptions({ options });
			const data = {
				token_id,
				api_token,
				client_instance_url,
			};
			APICheck(data)
				.then((res) => {
					if (res.success) {
						window.location.href =
							"/wp-admin/admin.php?page=openasset#/settings?installComplete=true";
						window.location.reload(true); // Force a reload from the server
					} else {
						setIsLoading(false);
						console.log(res.data); // Display the error message from the server
						showToast(res.data, "error");
					}
				})
				.catch((error) => {
					setIsLoading(false);
					console.log(error); // Display the error message from the server
					showToast(error, "error");
				});
		}
	};

	useEffect(() => {
		const updateOptions = (settings) =>
			setOptions({ ...options, ...settings });
		const res = fetchOptions({ updateOptions }).then((res) =>
			setProcessing(false)
		);

		showPromiseToast(res);
	}, []);
	return (
		<Layout>
			<div className='overflow-hidden bg-white py-10 rounded'>
				<div className='mx-auto max-w-7xl px-6 lg:px-8'>
					<div className='mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none'>
						<div className='w-full max-w-lg mx-auto drop-shadow-lg rounded overflow-hidden bg-white'>
							<div className='p-4 bg-[#22706f]'>
								<img
									src={pluginUrl + "assets/img/oa-logo.webp"}
									alt='OpenAsset Logo'
									width='200px'
									height='36px'
								/>
							</div>
							<div className='p-4 border-b-2 border-gray-100'>
								<h2 class='text-lg font-bold'>
									Connect to OpenAsset
								</h2>
							</div>
							<div className='content p-4'>
								{isLoading ? (
									// Show loading spinner and text when isLoading is true
									<div>
										<div className='flex justify-center items-center'>
											<div className='animate-spin rounded-full h-16 w-16 border-b-2 border-gray-900'></div>
										</div>
										<p class='text-center mt-3'>
											Checking Credentials...
										</p>
									</div>
								) : (
									<div>
										<p>
											An OpenAsset Wordpress Plugin
											License is required to connect and
											access data. Please contact your
											Customer Success manager if you do
											not have one.
										</p>
										<div className='py-3 space-y-5'>
											<div className='flex flex-col gap-2'>
												<TextInput
													id='client-instance-url'
													label='Your OpenAsset Instance URL'
													value={
														options[
															"general-settings"
														]["client-instance-url"]
													}
													setOption={updateOption}
													required={true}
												/>
												<TextInput
													id='your-real-token-id'
													label='Token ID'
													value={
														options[
															"general-settings"
														]["your-real-token-id"]
													}
													setOption={updateOption}
													required={true}
												/>
												<TextInput
													id='your-real-api-token'
													label='API Token'
													value={
														options[
															"general-settings"
														]["your-real-api-token"]
													}
													setOption={updateOption}
													type='password'
													required={true}
												/>
											</div>
										</div>
										<div className='px-3 sm:px-0 py-4'>
											<button
												type='submit'
												className='button button-primary'
												onClick={onSave}
											>
												Begin Install
											</button>
										</div>
									</div>
								)}
							</div>
						</div>
					</div>
				</div>
			</div>
		</Layout>
	);
};

export default Dashboard;
