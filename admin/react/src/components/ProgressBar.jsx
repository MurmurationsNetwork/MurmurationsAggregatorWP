import PropTypes from 'prop-types'

export default function ProgressBar({ progress }) {
  return (
    <div className="relative mt-6">
      <div className="w-full h-8 bg-orange-300" value={progress} max="100" />
      <div
        className="absolute top-0 left-0 bottom-0 bg-orange-500"
        style={{ width: `${progress}%` }}
      />
      <div className="absolute text-white font-bold top-1.5 left-0 right-0 text-center">
        {progress.toFixed(0)}%
      </div>
    </div>
  )
}

ProgressBar.propTypes = {
  progress: PropTypes.number.isRequired
}
