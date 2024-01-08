import PropTypes from 'prop-types'

export default function ProgressBar({ progress }) {
  return (
    <div className="relative mt-6">
      <div className="h-8 w-full bg-orange-300" value={progress} max="100" />
      <div
        className="absolute bottom-0 left-0 top-0 bg-orange-500"
        style={{ width: `${progress}%` }}
      />
      <div className="absolute left-0 right-0 top-1.5 text-center font-bold text-white">
        {progress.toFixed(0)}%
      </div>
    </div>
  )
}

ProgressBar.propTypes = {
  progress: PropTypes.number.isRequired
}
