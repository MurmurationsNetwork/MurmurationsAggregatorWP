import ReactDiffViewer from 'react-diff-viewer-continued'
import PropTypes from 'prop-types'

export default function PopupBox({
  isPopupOpen,
  setIsPopupOpen,
  originalJson,
  setOriginalJson,
  modifiedJson,
  setModifiedJson
}) {
  const originalString = JSON.stringify(originalJson, null, 2)
  const modifiedString = JSON.stringify(modifiedJson, null, 2)

  const handlePopupClose = () => {
    setIsPopupOpen(false)
    setOriginalJson({})
    setModifiedJson({})
  }

  return (
    <div>
      {isPopupOpen ? (
        <div className="fixed inset-0 flex justify-center items-center z-50">
          <div className="w-1/2 bg-white p-4 rounded-lg border border-gray-300">
            <div className="text-lg font-bold mb-4">See Updates</div>
            <hr />
            <div className="flex">
              <div className="max-h-80 overflow-y-auto">
                <ReactDiffViewer
                  oldValue={originalString}
                  newValue={modifiedString}
                  splitView={true}
                />
              </div>
            </div>
            <hr />
            <button
              className={`my-1 mx-2 max-w-fit rounded-full bg-red-500 px-4 py-2 font-bold text-white text-base active:scale-90 hover:scale-110 hover:bg-red-400 disabled:opacity-75`}
              onClick={() => handlePopupClose()}
            >
              Close
            </button>
          </div>
        </div>
      ) : null}
    </div>
  )
}

PopupBox.propTypes = {
  isPopupOpen: PropTypes.bool.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  originalJson: PropTypes.object.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  modifiedJson: PropTypes.object.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}
