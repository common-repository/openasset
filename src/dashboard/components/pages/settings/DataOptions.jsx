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
	saveOptions,
	runUpdate,
	fetchData,
	updateSortValues,
	fetchSyncingData,
} from "../../../api/settings";
import SettingsLayout from "../../layout/SettingsLayout";
import {
	SettingsCard,
	MultiSelectInput,
	SelectInput,
	TextInput,
	SyncingBanner,
} from "../../templates";

const DataOptions = () => {
	const [processing, setProcessing] = useState(true);
	const [isLoading, setIsLoading] = useState(false);
	const [syncData, setSyncData] = useState({
		projectsSynced: '*',
		employeesSynced: '*',
		totalProjects: window.pluginData.openassetTotalProjects,
		totalEmployees: window.pluginData.openassetTotalEmployees,
		syncRunning: window.pluginData.openassetSyncRunning,
	});

	const [options, setOptions] = useState({
		"data-options": {},
	});
	const [data, setData] = useState({});

	const updateOption = (value, id) => {
		setOptions({
			...options,
			"data-options": {
				...options["data-options"],
				[id]: value,
			},
		});
	};

	const onSave = () => {
		if (!processing) {
			setIsLoading(true);
			const res = saveOptions({ options }).then(() => {
				const data = {
					"sort-by": {
						project: options["data-options"]["project-sort-by"],
						employee: options["data-options"]["employee-sort-by"],
					},
				};
				updateSortValues(data);
			});
			showPromiseToastRefresh(res, "", "Settings updated!");
		}
	};

	const onRun = () => {
		if (!processing) {
			setIsLoading(true);
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
		}
	};

	useEffect(() => {

		const updateOptions = (settings) =>
			setOptions({ ...options, ...settings });

		const res = fetchOptions({ updateOptions }).then((res) => {
			fetchData().then((data) => {
				setData(data);
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
					Data Options
				</h1>
				<p className='text-lg leading-7 text-gray-900 px-3 sm:px-0 italic'>
					Set the information in OpenAsset that you would like to be
					available to your website
				</p>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					File Options
				</h2>
				<div className='px-3 sm:px-0'>
					<MultiSelectInput
						id='file-options'
						label='File Fields'
						values={options["data-options"]["file-options"]}
						options={[
							{
								key: "copyright_holder",
								label: "Copyright holder",
							},
							{
								key: "photographer",
								label: "Photographer",
							},
							{
								key: "caption",
								label: "Caption",
							},
							{
								key: "description",
								label: "Description",
							},
						]}
						setOption={updateOption}
					/>
				</div>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Project Options
				</h2>
				{/* <div className='block sm:flex gap-10 sm:max-w-[80%] px-3 sm:px-0'> */}
				<div className='px-3 sm:px-0'>
					{data["fields"] && data["fields"]["project"] ? (
						<MultiSelectInput
							id='project-criteria-fields'
							label='Project Fields'
							values={
								options["data-options"][
									"project-criteria-fields"
								]
							}
							options={(data["fields"]["project"] || [])
								.filter(
									(field) =>
										field.name !== "Show on Website" &&
										field.name !== "Roles"
								)
								.map((field) => ({
									key: field.id,
									label: field.name,
									disabled: field.rest_code === "name", // Mark the field as disabled if rest_code is 'name'
								}))}
							setOption={updateOption}
						/>
					) : (
						<div class='py-5'>
							<div className='flex justify-center items-center'>
								<div className='animate-spin rounded-full h-16 w-16 border-b-2 border-gray-900'></div>
							</div>
							<p className='text-center mt-3'>Loading Fields</p>
						</div>
					)}
				</div>
				{/* <div className='hidden sm:block w-[1px] h-100 bg-[#D9D9D9]' />
				<div className='mt-5 sm:hidden w-100'></div> */}
				{/* keywords were here */}
				{/* </div> */}
				<div className='px-3 sm:px-0'>
					{data["project-keyword-categories"] ? (
						<MultiSelectInput
							id='project-criteria-keyword-categories'
							label='Project Keyword Categories'
							values={
								options["data-options"][
									"project-criteria-keyword-categories"
								]
							}
							options={(
								data["project-keyword-categories"] || []
							).map((keyword) => ({
								key: keyword.id,
								label: keyword.name,
							}))}
							setOption={updateOption}
						/>
					) : (
						<div class='py-5'>
							<div className='flex justify-center items-center'>
								<div className='animate-spin rounded-full h-16 w-16 border-b-2 border-gray-900'></div>
							</div>
							<p className='text-center mt-3'>Loading Fields</p>
						</div>
					)}
				</div>
				<div className='flex flex-col sm:items-end sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%] px-3 sm:px-0'>
					{data["fields"] && data["fields"]["project"] && (
						<SelectInput
							id='project-sort-by'
							label='Sort projects by'
							value={options["data-options"]["project-sort-by"]}
							options={Object.fromEntries(
								(data["fields"]["project"] || [])
									.filter(
										(field) =>
											field.name !== "Show on Website" &&
											field.name !== "Roles"
									)
									.map((field) => [
										field.id.toString(),
										field.name,
									])
							)}
							setOption={updateOption}
						/>
					)}
					<SelectInput
						id='project-order-by'
						label='Order projects by'
						value={options["data-options"]["project-order-by"]}
						options={{
							Asc: "Ascending",
							Desc: "Descending",
						}}
						setOption={updateOption}
					/>
				</div>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Project Images
				</h2>
				<p><i>The maximum images per project does not include the hero image as this will always be included.</i></p>
				<div className='flex flex-col sm:items-end sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%] px-3 sm:px-0'>
					<SelectInput
						id='project-images-tagged-show-on-website'
						label='Only get images that are selected to “Show on Website”'
						value={
							options["data-options"][
								"project-images-tagged-show-on-website"
							]
						}
						options={{
							yes: "Yes",
							no: "No",
						}}
						setOption={updateOption}
					/>
					<TextInput
						id='maximum-images-per-project'
						type='number'
						label='Maximum Images Per Project'
						value={
							options["data-options"][
								"maximum-images-per-project"
							]
						}
						setOption={updateOption}
					/>
					<SelectInput
						id='project-images-sort-by'
						label='Sort images by'
						value={
							options["data-options"]["project-images-sort-by"]
						}
						options={{
							created: "Created At",
							uploaded: "Uploaded At",
							updated: "Updated At",
							rank: "Rank",
							project_display_order: "Project File Display Order",
						}}
						setOption={updateOption}
					/>
					<SelectInput
						id='project-images-order-by'
						label='Order images by'
						value={
							options["data-options"]["project-images-order-by"]
						}
						options={{
							Asc: "Ascending",
							Desc: "Descending",
						}}
						setOption={updateOption}
					/>
				</div>

				<h3 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Employees Options
				</h3>
				<div className='px-3 sm:px-0'>
					{data["fields"] && data["fields"]["employee"] ? (
						<MultiSelectInput
							id='employee-criteria-fields'
							label='Employee Fields'
							values={
								options["data-options"][
									"employee-criteria-fields"
								]
							}
							options={(data["fields"]["employee"] || [])
								.filter(
									(field) =>
										field.name !== "Show on Website" &&
										field.name !== "Roles"
								)
								.map((field) => ({
									key: field.id,
									label: field.name,
									disabled:
										field.rest_code === "first_name" ||
										field.rest_code === "last_name", // Mark the field as disabled if rest_code is 'first_name' or 'last_name'
								}))}
							setOption={updateOption}
						/>
					) : (
						<div class='py-5'>
							<div className='flex justify-center items-center'>
								<div className='animate-spin rounded-full h-16 w-16 border-b-2 border-gray-900'></div>
							</div>
							<p className='text-center mt-3'>Loading Fields</p>
						</div>
					)}
				</div>

				<div className='flex flex-col sm:items-end sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%] px-3 sm:px-0'>
					{data["fields"] && data["fields"]["employee"] && (
						<SelectInput
							id='employee-sort-by'
							label='Sort employees by'
							value={options["data-options"]["employee-sort-by"]}
							options={Object.fromEntries(
								(data["fields"]["employee"] || [])
									.filter(
										(field) =>
											field.name !== "Show on Website" &&
											field.name !== "Roles"
									)
									.map((field) => [
										field.id.toString(),
										field.name,
									])
							)}
							setOption={updateOption}
						/>
					)}
					<SelectInput
						id='employee-order-by'
						label='Order employees by'
						value={options["data-options"]["employee-order-by"]}
						options={{
							Asc: "Ascending",
							Desc: "Descending",
						}}
						setOption={updateOption}
					/>
				</div>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Employee Images
				</h2>
				<p><i>The maximum images per employee does not include the primary photo as this will always be included.</i></p>
				<div className='flex flex-col sm:items-end sm:grid grid-cols-2 gap-x-10 gap-y-6 max-w-none sm:max-w-[80%] px-3 sm:px-0'>
					<SelectInput
						id='employee-images-tagged-show-on-website'
						label='Only get images that are selected to “Show on Website”'
						value={
							options["data-options"][
								"employee-images-tagged-show-on-website"
							]
						}
						options={{
							yes: "Yes",
							no: "No",
						}}
						setOption={updateOption}
					/>
					<TextInput
						id='maximum-images-per-employee'
						type='number'
						label='Maximum Images Per Employee'
						value={
							options["data-options"][
								"maximum-images-per-employee"
							]
						}
						setOption={updateOption}
					/>
					<SelectInput
						id='employee-images-sort-by'
						label='Sort images by'
						value={
							options["data-options"]["employee-images-sort-by"]
						}
						options={{
							created: "Created At",
							uploaded: "Uploaded At",
							updated: "Updated At",
							rank: "Rank",
						}}
						setOption={updateOption}
					/>
					<SelectInput
						id='employee-images-order'
						label='Order images by'
						value={options["data-options"]["employee-images-order"]}
						options={{
							Asc: "Ascending",
							Desc: "Descending",
						}}
						setOption={updateOption}
					/>
				</div>

				<h2 className='text-xl font-semibold leading-7 text-gray-900 px-3 sm:px-0'>
					Role Options
				</h2>

				<div className='px-3 sm:px-0'>
					{data["grid-columns"] ? (
						<MultiSelectInput
							id='roles-criteria-fields'
							label='Role Fields'
							values={
								options["data-options"]["roles-criteria-fields"]
							}
							options={(data["grid-columns"] || [])
								.filter(
									(field) => field.name !== "Role Sync Id"
								)
								.map((role) => ({
									key: role.id,
									label: role.name,
								}))}
							setOption={updateOption}
						/>
					) : (
						<div class='py-5'>
							<div className='flex justify-center items-center'>
								<div className='animate-spin rounded-full h-16 w-16 border-b-2 border-gray-900'></div>
							</div>
							<p className='text-center mt-3'>Loading Fields</p>
						</div>
					)}
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

export default DataOptions;
