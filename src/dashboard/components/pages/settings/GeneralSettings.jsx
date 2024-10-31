/**
 * External dependencies.
 */
import { useState, useEffect } from "@wordpress/element";

/**
 * Internal dependencies.
 */
import { showPromiseToast, showPromiseToastRefresh } from "../../../utils";
import {
	fetchOptions,
	fetchData,
	saveOptions,
	runUpdate,
	updateFrequency,
	fetchSyncingData,
} from "../../../api/settings";
import SettingsLayout from "../../layout/SettingsLayout";
import {
	SettingsCard,
	SelectInput,
	TextInput,
	SyncingBanner,
} from "../../templates";

const GeneralSettings = () => {
	const [processing, setProcessing] = useState(true);
	const [isLoading, setIsLoading] = useState(false);
	const [syncData, setSyncData] = useState({
		projectsSynced: '*',
		employeesSynced: '*',
		totalProjects: window.pluginData.openassetTotalProjects,
		totalEmployees: window.pluginData.openassetTotalEmployees,
		syncRunning: window.pluginData.openassetSyncRunning
	});

	const [options, setOptions] = useState({
		"general-settings": {},
	});

	const [projectShow, setProjectShow] = useState(false);
	const [employeeShow, setEmployeeShow] = useState(false);

	const updateOption = (value, id) => {
		setOptions({
			...options,
			"general-settings": {
				...options["general-settings"],
				[id]: value,
			},
		});
	};

	const onSave = () => {
		if (!processing) {
			setIsLoading(true);
			const res = saveOptions({ options });
			const data = {
				frequency:
					options["general-settings"]["feed-frequency"] ||
					"openasset_24",
			};
			updateFrequency(data);
			showPromiseToastRefresh(res, "", "Settings updated!");
		}
	};

	const onRun = () => {
		if (!processing) {
			setIsLoading(true);
			try {
				const response = saveOptions({ options }).then(() => {
					const res = runUpdate();
					showPromiseToastRefresh(
						res,
						"Syncing with OpenAsset",
						"Sync running"
					);
				});
				showPromiseToast(
					response,
					"Updating settings",
					"Settings updated!"
				);
			} catch (error) {
				console.error("Error occurred:", error);
				// Handle any errors here
				setIsLoading(false);
			}
		}
	};

	useEffect(() => {

		const updateOptions = (settings) =>
			setOptions({ ...options, ...settings });
		const res = fetchOptions({ updateOptions }).then((res) => {
			fetchData().then((res) => {
				res["fields"] &&
					res["fields"]["project"] &&
					setProjectShow(
						res["fields"]["project"].some(
							(project) =>
								project.rest_code &&
								project.rest_code.startsWith("show_on_website")
						)
					);
				res["fields"] &&
					res["fields"]["employee"] &&
					setEmployeeShow(
						res["fields"]["employee"].some(
							(employee) =>
								employee.rest_code &&
								employee.rest_code.startsWith("show_on_website")
						)
					);
				setProcessing(false);
			});
		});

		showPromiseToast(res);
	}, []);

	useEffect(() => {
		const intervalId = setInterval(() => {
			fetchSyncingData().then((data) => {
				setSyncData(data);
			});
		}, 5000); // Poll every 5 seconds

		return () => clearInterval(intervalId);
	}, []);

	return (
		<SettingsLayout>
			<SettingsCard
				onSave={onSave}
				onRun={onRun}
				isLoading={isLoading || syncData.syncRunning == 1}
			>
				<h1 className='text-2xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					General Settings
				</h1>
				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Feed Settings
				</h2>
				<div className='flex flex-col sm:items-end px-3 sm:px-0 sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%]'>
					<TextInput
						id='client-instance-url'
						label='Your OpenAsset Instance URL'
						value={
							options["general-settings"]["client-instance-url"]
						}
						setOption={updateOption}
						order='order-1 sm:order-1'
						disabled={true}
					/>
					<SelectInput
						id='feed-frequency'
						label='Auto Sync Frequency'
						value={options["general-settings"]["feed-frequency"]}
						options={{
							none: "None",
							openasset_8: "Every 8 Hours",
							openasset_24: "Every 24 Hours",
						}}
						setOption={updateOption}
						order='order-4 sm:order-2'
					/>
					<TextInput
						id='your-real-token-id'
						label='Token ID'
						value={
							options["general-settings"]["your-real-token-id"]
						}
						setOption={updateOption}
						order='order-2 sm:order-3'
					/>
					<SelectInput
						id='enable-logging'
						label='Enable Logging?'
						value={options["general-settings"]["enable-logging"]}
						options={{
							true: "Yes",
							false: "No",
						}}
						setOption={updateOption}
						order='order-5 sm:order-4'
					/>
					<TextInput
						id='your-real-api-token'
						label='API Token'
						value={
							options["general-settings"]["your-real-api-token"]
						}
						setOption={updateOption}
						type='password'
						order='order-3 sm:order-5'
					/>
				</div>

				<div className='border-b w-100 border-[#E6E6E6] pt-3 max-w-none sm:max-w-[80%] mx-3 sm:mx-0'></div>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					WordPress Settings
				</h2>

				<h3 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Projects
				</h3>
				<h4 className='text-md font-semibold leading-7 text-red-600 px-3 sm:px-0'>
					{!projectShow &&
						'Cannot sync projects because the "Show on Website" field is not created in OpenAsset for projects. Please create the field and sync again.'}
				</h4>
				<div className='flex flex-col sm:items-end px-3 sm:px-0 sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%]'>
					<TextInput
						id='project-name-plural'
						label='Projects Name (plural)'
						placeholder='Eg. projects'
						value={
							options["general-settings"]["project-name-plural"]
						}
						setOption={updateOption}
					/>
					<TextInput
						id='project-name-singular'
						label='Projects Name (singular)'
						placeholder='Eg. project'
						value={
							options["general-settings"]["project-name-singular"]
						}
						setOption={updateOption}
					/>
					<TextInput
						id='project-url-key'
						label='Projects URL Key'
						value={options["general-settings"]["project-url-key"]}
						setOption={updateOption}
						placeholder='Eg. projects'
					/>
					<SelectInput
						id='project-show'
						label='Sync Projects?'
						value={options["general-settings"]["project-show"]}
						options={
							projectShow
								? {
										yes: "Yes",
										no: "No",
								  }
								: {
										no: "No",
								  }
						}
						setOption={updateOption}
					/>
				</div>

				<h3 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Employees
				</h3>
				<h4 className='text-md font-semibold leading-7 text-red-600 px-3 sm:px-0'>
					{!employeeShow &&
						'Cannot sync employees because the "Show on Website" field is not created in OpenAsset for employees. Please create the field and sync again.'}
				</h4>
				<div className='flex flex-col sm:items-end px-3 sm:px-0 sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%]'>
					<TextInput
						id='employee-name-plural'
						label='Employees Name (plural)'
						placeholder='Eg. employees'
						value={
							options["general-settings"]["employee-name-plural"]
						}
						setOption={updateOption}
					/>
					<TextInput
						id='employee-name-singular'
						label='Employees Name (singular)'
						placeholder='Eg. employee'
						value={
							options["general-settings"][
								"employee-name-singular"
							]
						}
						setOption={updateOption}
					/>
					<TextInput
						id='employee-url-key'
						label='Employees URL Key'
						value={options["general-settings"]["employee-url-key"]}
						setOption={updateOption}
						placeholder='Eg. employees'
					/>
					<SelectInput
						id='employee-show'
						label='Sync Employees?'
						value={options["general-settings"]["employee-show"]}
						options={
							employeeShow
								? {
										yes: "Yes",
										no: "No",
								  }
								: {
										no: "No",
								  }
						}
						setOption={updateOption}
					/>
				</div>
			</SettingsCard>
			{syncData.syncRunning == 1 &&
				options &&
				options["general-settings"] &&
				options["general-settings"]["project-name-plural"] && (
					<SyncingBanner
						options={options}
						syncData={syncData}
					/>
				)}
			{syncData.syncRunning == 2 &&
				options &&
				options["general-settings"] &&
				options["general-settings"]["project-name-plural"] && (
					<div
						className='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded fixed md:bottom-12 bottom-0.5 m-4 md:m-0'
						role='alert'
					>
						<strong className='font-bold'>
							Syncing terminated due to an API error.
						</strong>
						<span> - </span>
						<span className='block sm:inline'>
							Please try again or contact support.
						</span>
					</div>
				)}
		</SettingsLayout>
	);
};

export default GeneralSettings;
