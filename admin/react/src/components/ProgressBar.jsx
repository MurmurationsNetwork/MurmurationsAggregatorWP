import PropTypes from 'prop-types'

export default function ProgressBar({ progress }) {
  return (
    <div className="relative mt-6">
      <progress
        className="w-full bg-orange-500 h-8 mt-2 rounded"
        value={progress}
        max="100"
      />
      <div className="absolute text-white top-3.5 left-0 right-0 text-center">
        {progress.toFixed(0)}%
      </div>
    </div>
  )
}

ProgressBar.propTypes = {
  progress: PropTypes.number.isRequired
}
