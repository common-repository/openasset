import { useState, useEffect } from "@wordpress/element";
import { forceStopSync } from "../../api/settings";
import syncingImg from "../../../assets/syncing.svg";
import notSyncingImg from "../../../assets/not-syncing.svg";

const SyncingBanner = ({ options, syncData }) => {
	const [imgSrc, setImgSrc] = useState(syncingImg);
	const [syncingText, setSyncingText] = useState("Syncing with OpenAsset...");
	const [showDetails, setShowDetails] = useState(true);

	const handleForceStopSync = async () => {
		setSyncingText("Stopping sync...");
		setImgSrc(notSyncingImg);
		setShowDetails(false);
		await forceStopSync();
	};

	return (
		<div
			className='bg-sky-100 border border-sky-400 text-sky-700 px-4 py-3 rounded fixed md:bottom-12 bottom-0.5 m-4 md:m-0 flex justify-between items-center gap-5 mx-auto max-w-6xl sm:px-6 lg:px-8'
			role='alert'
		>
			<img
				src={imgSrc}
				alt='Syncing Status'
				className='w-6 h-6 animate-spin'
			/>
			<div>{syncingText}</div>
			{showDetails && (
				<>
					{options["general-settings"]["project-show"] == 'yes' && (
						<div>
							{syncData.projectsSynced} of{" "}
							{syncData.totalProjects}{" "}
							{options["general-settings"][
								"project-name-plural"
							] || "Projects"}
						</div>
					)}
					{options["general-settings"]["employee-show"] == 'yes' && (
						<div>
							{syncData.employeesSynced} of {syncData.totalEmployees}{" "}
							{options["general-settings"]["employee-name-plural"] ||
								"Employees"}
						</div>
					)}
					<button
						className='run-now button button-primary'
						onClick={handleForceStopSync}
					>
						Stop this sync
					</button>
				</>
			)}
		</div>
	);
};

export default SyncingBanner;
