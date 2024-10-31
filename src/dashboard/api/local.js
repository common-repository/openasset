/**
 * External dependencies.
 */
import apiFetch from "@wordpress/api-fetch";

/**
 * Internal dependencies.
 */
const { apiRoute } = window.openAssetPluginState;
import { addQueryArgs } from "@wordpress/url";
export async function getOption(key = false) {
	const queryParams = key && !empty(key) ? { key } : {};

	return await apiFetch({
		path: addQueryArgs(`/${apiRoute}/options/get-settings`, queryParams),
	}).then((response) => response);
}

export async function getData(key = false) {
	const queryParams = key && !empty(key) ? { key } : {};

	return await apiFetch({
		path: addQueryArgs(`/${apiRoute}/options/get-data`, queryParams),
	}).then((response) => response);
}

export async function setOptions(options) {
	const data = {
		options,
	};

	return await apiFetch({
		path: `/${apiRoute}/options/set-settings`,
		method: "POST",
		data,
	}).then((response) => response);
}

export async function runFeedUpdate() {
	return await apiFetch({
		path: `/${apiRoute}/options/run`,
	}).then((response) => response);
}

export async function frequency(data) {
	return await apiFetch({
		path: `/${apiRoute}/options/frequency`,
		method: "POST",
		data,
	}).then((response) => response);
}

export async function sortValues(data) {
	return await apiFetch({
		path: `/${apiRoute}/options/sort`,
		method: "POST",
		data,
	}).then((response) => response);
}

export async function checkCredentials(data) {
	return await apiFetch({
		path: `/${apiRoute}/options/check`,
		method: "POST",
		data,
	}).then((response) => response);
}

export async function getSyncingData() {
	return await apiFetch({
		path: `/${apiRoute}/options/sync-status`,
	}).then((response) => response);
}

export async function stopSync() {
	return await apiFetch({
		path: `/${apiRoute}/options/stop-sync`,
		method: "POST",
	}).then((response) => response);
}
