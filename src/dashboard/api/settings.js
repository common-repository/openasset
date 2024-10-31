/**
 * Internal dependencies.
 */
import {
	getOption,
	setOptions,
	checkCredentials,
	runFeedUpdate,
	frequency,
	getData,
	sortValues,
	getSyncingData,
	stopSync,
} from "./local";

export async function fetchOptions({ key = false, updateOptions }) {
	const settings = await getOption(key);
	if (Object.keys(settings).length !== 0 && settings.constructor === Object) {
		updateOptions(settings);
	}
}

export async function fetchData() {
	const data = await getData();
	return data;
}

export async function saveOptions({ options = {} }) {
	const res = await setOptions(options);

	return res;
}

export async function runUpdate() {
	const res = await runFeedUpdate();

	return res;
}

export async function updateFrequency(data) {
	const res = await frequency(data);

	return res;
}

export async function updateSortValues(data) {
	const res = await sortValues(data);

	return res;
}

export async function APICheck(data) {
	const res = await checkCredentials(data);

	return res;
}

export async function fetchSyncingData() {
	const res = await getSyncingData();

	return res;
}

export async function forceStopSync() {
	const res = await stopSync();

	return res;
}
