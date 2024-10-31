const MultiSelectInput = ({ id, label, description, values, options, setOption, ...props }) => {
    const handleChange = ( e, key ) => {
        const currentValues = values || [];
		let updatedValues;
		if (e.target.checked) {
			updatedValues = [...currentValues, key];
		} else {
			updatedValues = currentValues.filter((value) => value !== key);
		}
		setOption(updatedValues, id);
    };

	const toggleSelectAll = () => {
		const disabledOptionKeys = options
			.filter((option) => option.disabled)
			.map((option) => option.key);

		if (values?.length === options?.length) {
			// All options are selected, so deselect all but keep the disabled options
			setOption(disabledOptionKeys, id);
		} else {
			// Not all options are selected, so select all
			const allOptionKeys = options.map((option) => option.key);
			setOption(allOptionKeys, id);
		}
	};


    return (
		<fieldset>
			<legend className='text-sm font-semibold leading-6 text-gray-900'>
				{label && label}
			</legend>
			<p className='mt-1 text-sm leading-6 text-gray-600'>
				{description && description}
			</p>
			{options && (
				<button
					type='button'
					className='mt-4 border rounded p-2'
					onClick={toggleSelectAll}
				>
					{values?.length === options?.length
						? "Deselect All"
						: "Select All"}
				</button>
			)}

			{options && (
				<div className='mt-2 flex flex-col sm:w-fit max-h-[585px] sm:max-h-[400px] flex-wrap'>
					{options.map((option) => (
						<div
							key={option.key}
							className='relative flex gap-x-3 items-center sm:w-fit min-w-[130px]'
						>
							<div className='flex h-6 items-center'>
								<input
									id={id + `[${option.key}]`}
									name={id}
									type='checkbox'
									className={`h-4 w-4 shadow-none rounded border-gray-300 ${
										option.disabled
											? "bg-gray-200 cursor-not-allowed"
											: ""
									}`}
									onChange={(e) =>
										!option.disabled &&
										handleChange(e, option.key)
									}
									checked={
										option.disabled ||
										values?.includes(option.key)
									}
									disabled={option.disabled}
									{...props}
									autoComplete='off'
									data-lpignore='true'
									data-form-type='other'
								/>
							</div>
							<div className='text-sm leading-6 self-center'>
								<label
									htmlFor={id + `[${option.key}]`}
									className='font-medium text-gray-900'
								>
									{option?.label}
								</label>
								<p className='text-gray-500'>
									{option?.description}
								</p>
							</div>
						</div>
					))}
				</div>
			)}
		</fieldset>
	);
}

export default MultiSelectInput;