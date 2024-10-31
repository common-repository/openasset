const SettingsCard = ({
	title,
	description,
	children,
	onSave,
	onRun,
	isLoading = false,
}) => {
	return (
		<div className='bg-white rounded'>
			{title ||
				(description && (
					<div className='px-4 sm:px-0 py-4 flex justify-between'>
						<div>
							{title && (
								<h2 className='text-xl font-semibold leading-7 text-gray-900'>
									title
								</h2>
							)}
							{description && (
								<p className='mt-1 text-sm leading-6 text-gray-600'>
									description
								</p>
							)}
						</div>
					</div>
				))}
			<div className='py-3 space-y-5'>{children}</div>
			<div className='px-3 sm:px-0 pt-4 pb-16 flex gap-2 relative sm:fixed sm:top-[100px] sm:z-50 sm:right-[32px]'>
				<button
					type='submit'
					className='button button-primary'
					onClick={onSave}
					disabled={isLoading}
				>
					Save
				</button>
				{onRun && (
					<button
						type='submit'
						className='run-now button button-primary'
						onClick={onRun}
						disabled={isLoading}
					>
						Save & Sync
					</button>
				)}
			</div>
		</div>
	);
};

export default SettingsCard;
